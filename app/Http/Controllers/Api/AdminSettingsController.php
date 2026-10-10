<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeSetting;
use App\Models\OrganizationalUnit;
use App\Models\JobPosition;
use App\Models\EmployeePlacement;
use App\Models\OfficialDocumentSignatory;
use App\Models\SystemAuditTrail;
use App\Models\User;
use App\Models\Branch;
use App\Services\AdminSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminSettingsController extends Controller
{
    /**
     * Get all centralized settings, organizational structure, positions, and placements.
     */
    public function getMasterSettings()
    {
        try {
            $profile = AdminSettingsService::getInstituteProfile();
            $allSettings = AdminSettingsService::getAllSettingsMap();
            
            $orgUnits = OrganizationalUnit::with(['parent', 'children', 'jobPositions'])
                ->orderBy('sort_order')
                ->get();

            $positions = JobPosition::with(['organizationalUnit', 'currentPlacement.user'])
                ->orderBy('level_order')
                ->get();

            $placements = EmployeePlacement::with(['user', 'jobPosition', 'organizationalUnit', 'branch'])
                ->orderByDesc('id')
                ->get();

            $signatories = OfficialDocumentSignatory::with(['jobPosition', 'user'])
                ->orderBy('document_code')
                ->get();

            $users = User::select('id', 'name', 'email', 'role_id')->get();
            $branches = Branch::select('id', 'name', 'code', 'city')->get();

            try {
                $auditLogs = SystemAuditTrail::with('user')
                    ->where(function ($query) {
                        $query->where('event_type', 'like', '%setting%')
                            ->orWhere('event_type', 'like', '%org_unit%')
                            ->orWhere('event_type', 'like', '%job_position%')
                            ->orWhere('event_type', 'like', '%placement%')
                            ->orWhere('event_type', 'like', '%signator%');
                    })
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get();
            } catch (\Throwable $auditEx) {
                \Illuminate\Support\Facades\Log::warning('Audit logs query in getMasterSettings failed: ' . $auditEx->getMessage());
                $auditLogs = collect();
            }

            return response()->json([
                'status'      => 'success',
                'profile'     => $profile,
                'settings'    => $allSettings,
                'org_units'   => $orgUnits,
                'positions'   => $positions,
                'placements'  => $placements,
                'signatories' => $signatories,
                'users'       => $users,
                'branches'    => $branches,
                'audit_logs'  => $auditLogs,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('getMasterSettings failed: ' . $e->getMessage());
            return response()->json([
                'status'      => 'error',
                'message'     => 'تعذر تحميل بيانات الإعدادات الإدارية: ' . $e->getMessage(),
                'profile'     => AdminSettingsService::getInstituteProfile(),
                'settings'    => [],
                'org_units'   => [],
                'positions'   => [],
                'placements'  => [],
                'signatories' => [],
                'users'       => [],
                'branches'    => [],
                'audit_logs'  => [],
            ], 500);
        }
    }

    /**
     * Update Institute Profile & Identity Settings.
     */
    public function updateInstituteProfile(Request $request)
    {
        $validated = $request->validate([
            'state_name'             => 'required|string|max:255',
            'supervising_body'       => 'required|string|max:255',
            'supervising_department' => 'required|string|max:255',
            'institute_name'         => 'required|string|max:255',
            'branch_label'           => 'nullable|string|max:255',
            'phone'                  => 'nullable|string|max:100',
            'email'                  => 'nullable|string|max:255',
            'address'                => 'nullable|string|max:500',
            'website'                => 'nullable|string|max:255',
            'pobox'                  => 'nullable|string|max:100',
            'header_title'           => 'nullable|string|max:255',
            'footer_text'            => 'nullable|string|max:500',
            'logo'                   => 'nullable|image|max:4096',
            'stamp'                  => 'nullable|image|max:4096',
        ]);

        $userId = auth()->id() ?? 1;

        // Handle file uploads if present
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding', 'public');
            AdminSettingsService::setSetting('logo_url', '/storage/' . $path, 'branding', 'شعار المعهد المركزي', 'image', $userId);
        }

        if ($request->hasFile('stamp')) {
            $path = $request->file('stamp')->store('branding', 'public');
            AdminSettingsService::setSetting('stamp_url', '/storage/' . $path, 'branding', 'الختم الرسمي للمعهد', 'image', $userId);
        }

        foreach (['state_name', 'supervising_body', 'supervising_department', 'institute_name', 'branch_label', 'phone', 'email', 'address', 'website', 'pobox', 'header_title', 'footer_text'] as $field) {
            if ($request->has($field)) {
                $group = in_array($field, ['header_title', 'footer_text']) ? 'branding' : 'institute_profile';
                AdminSettingsService::setSetting($field, $request->input($field), $group, null, 'string', $userId);
            }
        }

        AdminSettingsService::clearCache();

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حفظ وتحديث بيانات وشعار المعهد المركزية بنجاح.',
            'profile' => AdminSettingsService::getInstituteProfile(),
        ]);
    }

    /**
     * Save or update an Organizational Unit.
     */
    public function saveOrgUnit(Request $request)
    {
        $validated = $request->validate([
            'id'          => 'nullable|integer|exists:organizational_units,id',
            'code'        => 'required|string|max:50',
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|in:general_admin,department,section,unit,committee,office',
            'parent_id'   => 'nullable',
            'sort_order'  => 'nullable|integer',
            'is_active'   => 'nullable|boolean',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $userId = auth()->id() ?? 1;
        $id = $request->input('id');
        $oldValues = $id ? OrganizationalUnit::find($id)?->toArray() : null;

        $parentId = !empty($validated['parent_id']) ? (int)$validated['parent_id'] : null;

        $unit = OrganizationalUnit::updateOrCreate(
            ['id' => $id],
            [
                'code'        => $validated['code'],
                'name'        => $validated['name'],
                'type'        => $validated['type'],
                'parent_id'   => $parentId,
                'sort_order'  => $validated['sort_order'] ?? 0,
                'is_active'   => $request->boolean('is_active', true),
                'notes'       => $validated['notes'] ?? null,
            ]
        );

        AdminSettingsService::recordAudit(
            $id ? 'update_org_unit' : 'create_org_unit',
            'OrganizationalUnit',
            $unit->id,
            $oldValues,
            $unit->toArray(),
            $userId,
            ($id ? "تعديل وحدة تنظيمية: " : "إضافة وحدة تنظيمية: ") . $unit->name
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حفظ الوحدة التنظيمية بنجاح.',
            'unit'    => $unit->load(['parent', 'children']),
        ]);
    }

    /**
     * Delete an Organizational Unit.
     */
    public function deleteOrgUnit($id)
    {
        $unit = OrganizationalUnit::findOrFail($id);
        
        // Prevent delete if has children or positions
        if ($unit->children()->count() > 0 || $unit->jobPositions()->count() > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'لا يمكن حذف هذه الوحدة التنظيمية لأنها مرتبطة بأقسام فرعية أو صفات وظيفية. يرجى إيقاف تفعيلها بدلاً من ذلك.',
            ], 422);
        }

        $old = $unit->toArray();
        $unitName = $unit->name;
        $unit->delete();

        AdminSettingsService::recordAudit(
            'delete_org_unit',
            'OrganizationalUnit',
            $id,
            $old,
            null,
            auth()->id() ?? 1,
            "حذف وحدة تنظيمية: {$unitName}"
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حذف الوحدة التنظيمية بنجاح.',
        ]);
    }

    /**
     * Save or update a Job Position (الصفة الوظيفية).
     */
    public function saveJobPosition(Request $request)
    {
        $validated = $request->validate([
            'id'                     => 'nullable|integer|exists:job_positions,id',
            'code'                   => 'required|string|max:50',
            'title'                  => 'required|string|max:255',
            'organizational_unit_id' => 'nullable',
            'level_order'            => 'nullable|integer',
            'is_active'              => 'nullable|boolean',
            'description'            => 'nullable|string|max:1000',
        ]);

        $userId = auth()->id() ?? 1;
        $id = $request->input('id');
        $oldValues = $id ? JobPosition::find($id)?->toArray() : null;

        $orgUnitId = !empty($validated['organizational_unit_id']) ? (int)$validated['organizational_unit_id'] : null;

        $pos = JobPosition::updateOrCreate(
            ['id' => $id],
            [
                'code'                   => $validated['code'],
                'title'                  => $validated['title'],
                'organizational_unit_id' => $orgUnitId,
                'level_order'            => $validated['level_order'] ?? 1,
                'is_active'              => $request->boolean('is_active', true),
                'description'            => $validated['description'] ?? null,
            ]
        );

        AdminSettingsService::recordAudit(
            $id ? 'update_job_position' : 'create_job_position',
            'JobPosition',
            $pos->id,
            $oldValues,
            $pos->toArray(),
            $userId,
            ($id ? "تعديل صفة وظيفية: " : "إضافة صفة وظيفية: ") . $pos->title
        );

        return response()->json([
            'status'   => 'success',
            'message'  => 'تم حفظ وتحديث الصفة الوظيفية بنجاح.',
            'position' => $pos->load('organizationalUnit'),
        ]);
    }

    /**
     * Delete a Job Position.
     */
    public function deleteJobPosition($id)
    {
        $pos = JobPosition::findOrFail($id);

        if ($pos->employeePlacements()->where('is_current', true)->count() > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'لا يمكن حذف هذه الصفة لوجود موظفين مسكنين عليها حالياً. يرجى تعديل التسكين أولاً أو إيقاف تفعيل الصفة.',
            ], 422);
        }

        $old = $pos->toArray();
        $title = $pos->title;
        $pos->delete();

        AdminSettingsService::recordAudit(
            'delete_job_position',
            'JobPosition',
            $id,
            $old,
            null,
            auth()->id() ?? 1,
            "حذف صفة وظيفية: {$title}"
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حذف الصفة الوظيفية بنجاح.',
        ]);
    }

    /**
     * Save or update Employee Placement (تسكين الموظف).
     */
    public function saveEmployeePlacement(Request $request)
    {
        $validated = $request->validate([
            'id'                     => 'nullable|integer|exists:employee_placements,id',
            'user_id'                => 'required|integer|exists:users,id',
            'job_position_id'        => 'required|integer|exists:job_positions,id',
            'organizational_unit_id' => 'nullable',
            'branch_id'              => 'nullable',
            'start_date'             => 'required|date',
            'end_date'               => 'nullable|date|after_or_equal:start_date',
            'is_current'             => 'nullable|boolean',
            'status'                 => 'required|string|in:active,historic,transferred',
            'decision_number'        => 'nullable|string|max:255',
            'notes'                  => 'nullable|string|max:1000',
        ]);

        $userId = auth()->id() ?? 1;
        $id = $request->input('id');
        $oldValues = $id ? EmployeePlacement::find($id)?->toArray() : null;

        $orgUnitId = !empty($validated['organizational_unit_id']) ? (int)$validated['organizational_unit_id'] : null;
        $branchId = !empty($validated['branch_id']) ? (int)$validated['branch_id'] : null;

        // If setting as current, mark other placements for this position/user as historic
        if ($request->boolean('is_current', true) && $validated['status'] === 'active') {
            EmployeePlacement::where('user_id', $validated['user_id'])
                ->where('job_position_id', $validated['job_position_id'])
                ->where('id', '!=', $id ?? 0)
                ->update(['is_current' => false]);
        }

        $placement = EmployeePlacement::updateOrCreate(
            ['id' => $id],
            [
                'user_id'                => $validated['user_id'],
                'job_position_id'        => $validated['job_position_id'],
                'organizational_unit_id' => $orgUnitId,
                'branch_id'              => $branchId,
                'start_date'             => $validated['start_date'],
                'end_date'               => $validated['end_date'] ?? null,
                'is_current'             => $request->boolean('is_current', true),
                'status'                 => $validated['status'],
                'decision_number'        => $validated['decision_number'] ?? null,
                'notes'                  => $validated['notes'] ?? null,
            ]
        );

        AdminSettingsService::recordAudit(
            $id ? 'update_placement' : 'create_placement',
            'EmployeePlacement',
            $placement->id,
            $oldValues,
            $placement->toArray(),
            $userId,
            "تسكين موظف: ID {$validated['user_id']} في الصفة {$validated['job_position_id']}"
        );

        return response()->json([
            'status'    => 'success',
            'message'   => 'تم تسجيل التسكين الوظيفي بنجاح.',
            'placement' => $placement->load(['user', 'jobPosition', 'organizationalUnit', 'branch']),
        ]);
    }

    /**
     * Delete an Employee Placement.
     */
    public function deleteEmployeePlacement($id)
    {
        $placement = EmployeePlacement::findOrFail($id);
        $old = $placement->toArray();
        $placement->delete();

        AdminSettingsService::recordAudit(
            'delete_placement',
            'EmployeePlacement',
            $id,
            $old,
            null,
            auth()->id() ?? 1,
            "حذف تسكين وظيفي: ID {$id}"
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حذف سجل التسكين بنجاح.',
        ]);
    }

    /**
     * Save official document signatories configuration.
     */
    public function saveDocumentSignatories(Request $request)
    {
        $validated = $request->validate([
            'signatories'                         => 'required|array',
            'signatories.*.document_code'         => 'required|string',
            'signatories.*.slot_key'              => 'required|string',
            'signatories.*.slot_label'            => 'required|string',
            'signatories.*.job_position_id'       => 'nullable',
            'signatories.*.user_id'               => 'nullable',
            'signatories.*.custom_title_override' => 'nullable|string|max:255',
            'signatories.*.is_active'             => 'nullable|boolean',
        ]);

        $userId = auth()->id() ?? 1;

        foreach ($validated['signatories'] as $sigData) {
            $posId = !empty($sigData['job_position_id']) ? (int)$sigData['job_position_id'] : null;
            $uId = !empty($sigData['user_id']) ? (int)$sigData['user_id'] : null;

            OfficialDocumentSignatory::updateOrCreate(
                [
                    'document_code' => $sigData['document_code'],
                    'slot_key'      => $sigData['slot_key'],
                ],
                [
                    'slot_label'            => $sigData['slot_label'],
                    'job_position_id'       => $posId,
                    'user_id'               => $uId,
                    'custom_title_override' => $sigData['custom_title_override'] ?? null,
                    'is_active'             => $sigData['is_active'] ?? true,
                ]
            );
        }

        AdminSettingsService::clearCache();

        AdminSettingsService::recordAudit(
            'update_signatories',
            'OfficialDocumentSignatory',
            0,
            null,
            $validated['signatories'],
            $userId,
            'تحديث إعدادات التوقيعات والاعتمادات الرسمية للمستندات والتقارير'
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حفظ وتحديث إعدادات التوقيعات الرسمية بنجاح.',
        ]);
    }
}
