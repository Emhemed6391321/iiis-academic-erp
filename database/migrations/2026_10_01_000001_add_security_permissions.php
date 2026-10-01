<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * CRIT-1 Security Fix Migration
 * 
 * Adds the 4 new security permissions required by the authorization layer:
 * - MANAGE_ROLES: Required to toggle role permissions (PermissionMatrixController)
 * - MANAGE_USER_PERMISSIONS: Required to grant/revoke user overrides (UserController)
 * - CHANGE_STUDENT_STATUS: Required for destructive student status changes (StudentFileController)
 * - APPROVE_STUDENT_DATA: Required to approve/revoke student data (StudentFileController)
 * 
 * These are auto-granted to super_admin and distributed to relevant roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        $newPermissions = [
            ['code' => 'MANAGE_ROLES',             'module' => 'security', 'display_name' => 'إدارة صلاحيات الأدوار'],
            ['code' => 'MANAGE_USER_PERMISSIONS',  'module' => 'security', 'display_name' => 'إدارة الصلاحيات الاستثنائية للمستخدمين'],
            ['code' => 'CHANGE_STUDENT_STATUS',    'module' => 'security', 'display_name' => 'تغيير حالة الطالب (فصل / تعليق / تخرج / نقل)'],
            ['code' => 'APPROVE_STUDENT_DATA',     'module' => 'security', 'display_name' => 'اعتماد وفك اعتماد بيانات الطالب'],
        ];

        foreach ($newPermissions as $perm) {
            // Use firstOrCreate to be safe on re-runs
            DB::table('permissions')->insertOrIgnore([
                'code'         => $perm['code'],
                'module'       => $perm['module'],
                'display_name' => $perm['display_name'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // Grant all new permissions to super_admin role automatically
        $superAdmin = DB::table('roles')->where('name', 'super_admin')->first();
        if ($superAdmin) {
            foreach ($newPermissions as $perm) {
                $permission = DB::table('permissions')->where('code', $perm['code'])->first();
                if ($permission) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id'       => $superAdmin->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }

        // Grant APPROVE_STUDENT_DATA + CHANGE_STUDENT_STATUS to hq_student_affairs
        $hqStudentAffairs = DB::table('roles')->where('name', 'hq_student_affairs')->first();
        if ($hqStudentAffairs) {
            foreach (['APPROVE_STUDENT_DATA', 'CHANGE_STUDENT_STATUS'] as $code) {
                $permission = DB::table('permissions')->where('code', $code)->first();
                if ($permission) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id'       => $hqStudentAffairs->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }

        // Grant MANAGE_ROLES + MANAGE_USER_PERMISSIONS to hq_it_office
        $hqIt = DB::table('roles')->where('name', 'hq_it_office')->first();
        if ($hqIt) {
            foreach (['MANAGE_ROLES', 'MANAGE_USER_PERMISSIONS'] as $code) {
                $permission = DB::table('permissions')->where('code', $code)->first();
                if ($permission) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id'       => $hqIt->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $codes = ['MANAGE_ROLES', 'MANAGE_USER_PERMISSIONS', 'CHANGE_STUDENT_STATUS', 'APPROVE_STUDENT_DATA'];
        $ids = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('code', $codes)->delete();
    }
};
