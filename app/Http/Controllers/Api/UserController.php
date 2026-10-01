<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\JobPosition;
use App\Models\EmployeePlacement;
use App\Models\Permission;
use App\Models\SystemAuditTrail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * استعراض قائمة المستخدمين مع التصفية والبحث والإحصائيات وعزل الفروع
     */
    public function index(Request $request): JsonResponse
    {
        $currentUser = Auth::user();
        $query = User::with([
            'role.permissions',
            'branch',
            'currentPlacement.jobPosition',
            'currentPlacement.organizationalUnit',
            'directPermissions'
        ]);

        // 1. تطبيق عزل الفروع: مستخدمو الفروع يشاهدون فقط مستخدمي فرعهم
        if ($currentUser && !$currentUser->hasGlobalAccessScope() && !empty($currentUser->branch_id)) {
            $query->where('branch_id', $currentUser->branch_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // 2. تصفية الدور الوظيفي
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        // 3. تصفية الحالة (نشط / غير نشط)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // 4. البحث النصي الذكي
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('national_id', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $users = $query->orderBy('id', 'desc')->get();

        // 5. حساب إحصائيات لوحة IAM
        $statsBase = User::query();
        if ($currentUser && !$currentUser->hasGlobalAccessScope() && !empty($currentUser->branch_id)) {
            $statsBase->where('branch_id', $currentUser->branch_id);
        }

        $stats = [
            'total_users'       => (clone $statsBase)->count(),
            'active_users'      => (clone $statsBase)->where('is_active', true)->count(),
            'inactive_users'    => (clone $statsBase)->where('is_active', false)->count(),
            'two_factor_users'  => (clone $statsBase)->where('two_factor_enabled', true)->count(),
            'branches_count'    => Branch::where('is_active', true)->count(),
        ];

        return response()->json([
            'success'       => true,
            'users'         => $users,
            'data'          => $users, // للتوافق التام مع أي نداء واجهة
            'roles'         => Role::with('permissions')->get(),
            'branches'      => Branch::all(['id', 'code', 'name', 'city']),
            'job_positions' => JobPosition::where('is_active', true)->get(['id', 'code', 'title', 'organizational_unit_id']),
            'stats'         => $stats,
        ]);
    }

    /**
     * إنشاء حساب مستخدم جديد
     */
    public function store(Request $request): JsonResponse
    {
        $currentUser = Auth::user();

        $validated = $request->validate([
            'name'               => 'required|string|max:150',
            'email'              => 'required|email|max:150|unique:users,email',
            'phone'              => 'nullable|string|max:30',
            'national_id'        => 'nullable|string|size:12|unique:users,national_id',
            'password'           => ['required', 'string', 'min:8'],
            'role_id'            => 'required|exists:roles,id',
            'branch_id'          => 'nullable|exists:branches,id',
            'job_position_id'    => 'nullable|exists:job_positions,id',
            'two_factor_enabled' => 'boolean',
            'is_active'          => 'boolean',
        ]);

        // إلزام مستخدم الفرع بإنشاء المستخدم ضمن فرعه فقط
        if ($currentUser && !$currentUser->hasGlobalAccessScope()) {
            $validated['branch_id'] = $currentUser->branch_id;
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['two_factor_enabled'] = $request->boolean('two_factor_enabled', false);

        $jobPositionId = $validated['job_position_id'] ?? null;
        unset($validated['job_position_id']);

        $user = User::create($validated);

        // ربط التسكين الإداري للموظف إن وجد
        if ($jobPositionId) {
            $jobPos = JobPosition::find($jobPositionId);
            EmployeePlacement::create([
                'user_id'                => $user->id,
                'job_position_id'        => $jobPositionId,
                'organizational_unit_id' => $jobPos ? $jobPos->organizational_unit_id : null,
                'branch_id'              => $user->branch_id,
                'start_date'             => Carbon::now(),
                'is_current'             => true,
                'status'                 => 'active',
                'notes'                  => 'تسكين وظيفي تلقائي عند إنشاء الحساب',
            ]);
        }

        // تسجيل العملية في سجل التدقيق الجنائي
        $this->logAudit(
            'USER_CREATED',
            "إنشاء حساب مستخدم جديد: {$user->name} ({$user->email}) وتعيين الدور: " . ($user->role ? $user->role->display_name : 'غير محدد'),
            $user->branch_id,
            [
                'created_user_id' => $user->id,
                'role_id'         => $user->role_id,
                'branch_id'       => $user->branch_id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء حساب المستخدم بنجاح وتعيين الصلاحيات المقررة.',
            'user'    => $user->load(['role', 'branch', 'currentPlacement.jobPosition']),
        ], 201);
    }

    /**
     * تعديل بيانات المستخدم
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $currentUser = Auth::user();

        // حظر التعديل عبر الفروع لغير مسؤولي الإدارة العامة
        if ($currentUser && !$currentUser->hasGlobalAccessScope() && $user->branch_id != $currentUser->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتعديل بيانات مستخدم يتبع فرعاً آخر.',
            ], 403);
        }

        $validated = $request->validate([
            'name'               => 'required|string|max:150',
            'email'              => 'required|email|max:150|unique:users,email,' . $user->id,
            'phone'              => 'nullable|string|max:30',
            'national_id'        => 'nullable|string|size:12|unique:users,national_id,' . $user->id,
            'role_id'            => 'required|exists:roles,id',
            'branch_id'          => 'nullable|exists:branches,id',
            'job_position_id'    => 'nullable|exists:job_positions,id',
            'two_factor_enabled' => 'boolean',
            'is_active'          => 'boolean',
            'password'           => ['nullable', 'string', 'min:8'],
        ]);

        if ($currentUser && !$currentUser->hasGlobalAccessScope()) {
            $validated['branch_id'] = $currentUser->branch_id;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $jobPositionId = $validated['job_position_id'] ?? null;
        unset($validated['job_position_id']);

        $user->update($validated);

        // تحديث أو إنشاء التسكين الوظيفي
        if ($jobPositionId) {
            $jobPos = JobPosition::find($jobPositionId);
            $currentPlacement = $user->currentPlacement;
            if ($currentPlacement) {
                $currentPlacement->update([
                    'job_position_id'        => $jobPositionId,
                    'organizational_unit_id' => $jobPos ? $jobPos->organizational_unit_id : $currentPlacement->organizational_unit_id,
                    'branch_id'              => $user->branch_id,
                ]);
            } else {
                EmployeePlacement::create([
                    'user_id'                => $user->id,
                    'job_position_id'        => $jobPositionId,
                    'organizational_unit_id' => $jobPos ? $jobPos->organizational_unit_id : null,
                    'branch_id'              => $user->branch_id,
                    'start_date'             => Carbon::now(),
                    'is_current'             => true,
                    'status'                 => 'active',
                ]);
            }
        }

        $this->logAudit(
            'USER_UPDATED',
            "تعديل بيانات الحساب للمستخدم: {$user->name} ({$user->email})",
            $user->branch_id,
            ['user_id' => $user->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات المستخدم بنجاح.',
            'user'    => $user->fresh(['role', 'branch', 'currentPlacement.jobPosition', 'directPermissions']),
        ]);
    }

    /**
     * تفعيل أو إيقاف حساب المستخدم
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $currentUser = Auth::user();

        // 1. لا يمكن للمستخدم إيقاف حسابه الشخصي
        if ($currentUser && $currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تعطيل أو إيقاف حسابك الشخصي النشط.',
            ], 422);
        }

        // 2. حماية المدير العام: لا يمكن إيقاف المدير العام إذا كان الحساب الوحيد النشط
        if ($user->isSuperAdmin() && $user->is_active) {
            $superAdminsCount = User::whereHas('role', function($q) {
                $q->where('name', 'super_admin');
            })->where('is_active', true)->count();

            if ($superAdminsCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إيقاف حساب المدير العام الوحيد الفعال في المنظومة.',
                ], 422);
            }
        }

        // 3. عزل الفروع
        if ($currentUser && !$currentUser->hasGlobalAccessScope() && $user->branch_id != $currentUser->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتعديل حالة مستخدم يتبع فرعاً آخر.',
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $actionText = $user->is_active ? 'تفعيل' : 'إيقاف وتعطيل';
        $this->logAudit(
            'USER_STATUS_TOGGLED',
            "{$actionText} حساب المستخدم: {$user->name} ({$user->email})",
            $user->branch_id,
            ['user_id' => $user->id, 'new_status' => $user->is_active]
        );

        return response()->json([
            'success'   => true,
            'is_active' => $user->is_active,
            'message'   => "تم {$actionText} حساب المستخدم بنجاح.",
            'user'      => $user->fresh(['role', 'branch', 'currentPlacement.jobPosition']),
        ]);
    }

    /**
     * إعادة ضبط كلمة المرور للمستخدم
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $currentUser = Auth::user();

        if ($currentUser && !$currentUser->hasGlobalAccessScope() && $user->branch_id != $currentUser->branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإعادة ضبط كلمة مرور مستخدم يتبع فرعاً آخر.',
            ], 403);
        }

        $request->validate([
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => 'required|string',
        ], [
            'password.required'  => 'يرجى إدخال كلمة المرور الجديدة.',
            'password.min'       => 'يجب ألا تقل كلمة المرور عن 6 خانات.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        $this->logAudit(
            'USER_PASSWORD_RESET',
            "إعادة ضبط كلمة المرور الرسمية لحساب المستخدم: {$user->name} ({$user->email})",
            $user->branch_id,
            ['user_id' => $user->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'تمت إعادة ضبط كلمة المرور للمستخدم بنجاح.',
        ]);
    }

    /**
     * جلب كافة الصلاحيات للمستخدم مع توضيح: الموروث من الدور، والممنوح/المحجوب استثنائياً
     */
    public function getUserPermissions(User $user): JsonResponse
    {
        $allPermissions = Permission::all();
        $rolePermissions = $user->role ? $user->role->permissions->pluck('id')->toArray() : [];
        $directPermissions = $user->directPermissions()->get();

        $permissionsDetailed = $allPermissions->map(function ($perm) use ($user, $rolePermissions, $directPermissions) {
            $isInherited = in_array($perm->id, $rolePermissions);
            $direct = $directPermissions->firstWhere('id', $perm->id);
            
            $overrideType = null; // null | 'granted' | 'revoked'
            if ($direct !== null) {
                $overrideType = (bool)$direct->pivot->is_granted ? 'granted' : 'revoked';
            }

            $effective = $user->hasPermission($perm->code);

            return [
                'id'             => $perm->id,
                'code'           => $perm->code,
                'module'         => $perm->module,
                'display_name'   => $perm->display_name,
                'is_inherited'   => $isInherited,
                'override_type'  => $overrideType, // 'granted', 'revoked', or null
                'effective'      => $effective,
            ];
        })->groupBy('module');

        return response()->json([
            'success'     => true,
            'user'        => $user->only(['id', 'name', 'email', 'role_id']),
            'role'        => $user->role,
            'permissions' => $permissionsDetailed,
        ]);
    }

    /**
     * حفظ التخصيصات والصلاحيات الخاصة للمستخدم (منح أو حجب استثنائي)
     */
    public function updateUserPermissions(Request $request, User $user): JsonResponse
    {
        $currentUser = Auth::user();

        // CRIT-3: منع رفع الصلاحيات الذاتي (Self Privilege Escalation)
        if (!$currentUser || $currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تعديل صلاحياتك الخاصة بنفسك.',
            ], 403);
        }

        // CRIT-3: Only users with MANAGE_USER_PERMISSIONS can override permissions
        if (!$currentUser->hasPermission('MANAGE_USER_PERMISSIONS')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: إدارة الصلاحيات الاستثنائية للمستخدمين تتطلب صلاحية خاصة.',
            ], 403);
        }


        $request->validate([
            'overrides'                 => 'present|array',
            'overrides.*.permission_id' => 'required|exists:permissions,id',
            'overrides.*.override_type' => 'nullable|in:granted,revoked,none',
        ]);

        // نقوم بإعداد مصفوفة المزامنة
        $syncData = [];
        foreach ($request->overrides as $item) {
            $type = $item['override_type'] ?? 'none';
            if ($type === 'granted') {
                $syncData[$item['permission_id']] = ['is_granted' => true];
            } elseif ($type === 'revoked') {
                $syncData[$item['permission_id']] = ['is_granted' => false];
            }
            // إذا كان none لا نضيفه للمزامنة وبالتالي يتم حذفه من الاستثناءات والعودة للدور
        }

        $user->directPermissions()->sync($syncData);

        $this->logAudit(
            'USER_PERMISSIONS_OVERRIDE',
            "تحديث الصلاحيات الاستثنائية الخاصة للمستخدم: {$user->name} ({$user->email})",
            $user->branch_id,
            ['user_id' => $user->id, 'overrides_count' => count($syncData)]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الصلاحيات الخاصة وتحديث صلاحيات المستخدم فورياً.',
            'user'    => $user->fresh(['role', 'directPermissions']),
        ]);
    }

    /**
     * تسجيل العملية في سجل التدقيق الجنائي
     */
    protected function logAudit(string $eventType, string $description, ?int $branchId, array $payload = []): void
    {
        try {
            SystemAuditTrail::create([
                'user_id'     => Auth::id(),
                'branch_id'   => $branchId ?? (Auth::user() ? Auth::user()->branch_id : null),
                'event_type'  => $eventType,
                'description' => $description,
                'ip_address'  => request()->ip(),
                'payload'     => array_merge($payload, [
                    'executor_id' => Auth::id(),
                    'user_agent'  => substr((string)request()->userAgent(), 0, 255),
                    'timestamp'   => Carbon::now()->toIso8601String(),
                ]),
                'created_at'  => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // تجاهل لتجنب تعطيل العملية في حال وجود اختلافات حقول
        }
    }
}
