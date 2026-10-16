<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Role;
use App\Models\Permission;

class PermissionMatrixController extends Controller
{
    /**
     * Get all roles with their assigned permissions grouped by module.
     */
    public function getMatrix(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy('module');

        return response()->json([
            'success' => true,
            'roles' => $roles,
            'modules' => $permissions,
        ]);
    }

    /**
     * Toggle a specific permission for a role.
     */
    public function toggleRolePermission(Request $request): JsonResponse
    {
        // CRIT-4: Only users with MANAGE_ROLES permission can modify role permissions
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        if (!$currentUser || !$currentUser->hasPermission('MANAGE_ROLES')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: تعديل مصفوفة الصلاحيات يتطلب صلاحية إدارة الأدوار.',
            ], 403);
        }

        $request->validate([
            'role_id'       => 'required|exists:roles,id',
            'permission_id' => 'required|exists:permissions,id',
            'enabled'       => 'nullable|boolean',
            'assigned'      => 'nullable|boolean',
        ]);

        $role = Role::findOrFail($request->role_id);
        $permission = Permission::findOrFail($request->permission_id);
        
        if ($role->name === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تقييد صلاحيات دور المدير العام.',
            ], 422);
        }

        $isEnabled = $request->has('enabled') ? $request->boolean('enabled') : $request->boolean('assigned');

        if ($isEnabled) {
            $role->permissions()->syncWithoutDetaching([$request->permission_id]);
        } else {
            $role->permissions()->detach([$request->permission_id]);
        }

        // تسجيل التغيير في سجل التدقيق الجنائي
        try {
            \App\Models\SystemAuditTrail::create([
                'user_id'     => \Illuminate\Support\Facades\Auth::id(),
                'branch_id'   => null,
                'event_type'  => 'ROLE_PERMISSION_TOGGLED',
                'description' => ($isEnabled ? 'منح' : 'سحب') . " صلاحية [{$permission->display_name}] للدور [{$role->display_name}]",
                'ip_address'  => $request->ip(),
                'payload'     => [
                    'role_id'       => $role->id,
                    'role_name'     => $role->name,
                    'permission_id' => $permission->id,
                    'action'        => $isEnabled ? 'attach' : 'detach',
                ],
                'created_at'  => \Carbon\Carbon::now(),
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث مصفوفة الصلاحيات بنجاح.',
            'role'    => $role->fresh('permissions'),
        ]);
    }

    /**
     * تعديل المسمى الوظيفي (display_name) للدور من مصفوفة الصلاحيات.
     * المسمى هو المصدر الوحيد: تُحدَّث الصفة المعتمدة المرتبطة به ليظهر نفس المسمى في الإعدادات والمستندات.
     */
    public function updateRoleTitle(Request $request, Role $role): JsonResponse
    {
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        if (!$currentUser || !($currentUser->hasPermission('MANAGE_ROLES') || $currentUser->hasPermission('permissions.manage'))) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: تعديل مسمى الدور يتطلب صلاحية إدارة الأدوار.',
            ], 403);
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:100',
        ]);

        $oldName = $role->display_name;
        $newName = trim($validated['display_name']);

        if ($newName === '') {
            return response()->json([
                'success' => false,
                'message' => 'المسمى الوظيفي لا يمكن أن يكون فارغاً.',
            ], 422);
        }

        $role->update(['display_name' => $newName]);
        \App\Services\AdminSettingsService::syncRolePositions();

        try {
            \App\Models\SystemAuditTrail::create([
                'user_id'     => \Illuminate\Support\Facades\Auth::id(),
                'branch_id'   => null,
                'event_type'  => 'ROLE_TITLE_UPDATED',
                'description' => "تعديل المسمى الوظيفي للدور [{$role->name}] من [{$oldName}] إلى [{$newName}]",
                'ip_address'  => $request->ip(),
                'payload'     => [
                    'role_id'  => $role->id,
                    'old_name' => $oldName,
                    'new_name' => $newName,
                ],
                'created_at'  => \Carbon\Carbon::now(),
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المسمى الوظيفي في المصفوفة والإعدادات المركزية.',
            'role'    => $role->fresh('permissions'),
        ]);
    }
}
