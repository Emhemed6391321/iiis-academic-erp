<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * مواءمة صلاحيات دور "مدير الفرع" مع التصميم المعتمد في AcademicSystemSeeder.
 * كان الخادم الحي يمنح هذا الدور الاعتماد المركزي للطلاب والتحويل (students.approve_hq و students.transfer)
 * ولا يمنحه الحضور والفروع والمناهج. هذا الترحيل يضبط الدور فقط ولا يمس أي دور آخر.
 * آمن للتكرار: يعيد المزامنة إلى الحالة المعتمدة في كل مرة.
 */
return new class extends Migration
{
    private const ROLE = 'branch_manager';

    private const DESIGNED_PERMISSIONS = [
        'branches.view',
        'students.view',
        'students.create',
        'students.submit_hq',
        'attendance.view',
        'attendance.record',
        'attendance.reports',
        'grades.view',
        'grades.enter_coursework',
        'grades.enter_final',
        'grades.submit_batch',
        'curriculum.view',
        'reports.print_official',
    ];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');
        if (!$roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('code', self::DESIGNED_PERMISSIONS)
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($roleId, $permissionIds) {
            DB::table('role_permissions')->where('role_id', $roleId)->delete();

            $rows = array_map(fn ($permissionId) => [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ], $permissionIds);

            if (!empty($rows)) {
                DB::table('role_permissions')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        // لا يُستعاد التكوين السابق تلقائياً: كان غير مقصود وقد يكون منح صلاحيات مركزية.
    }
};
