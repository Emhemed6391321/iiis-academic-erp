<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * يمنح مدير الفرع صلاحية students.transfer ليؤدي خطوات مسار النقل التي تخص فرعه:
 * رفع طلب نقل طالب من فرعه، وقرار قبول الطالب الوافد إلى فرعه.
 * خطوة الإفادة المركزية والاعتماد النهائي تبقى للإدارة العامة، والخادم يتحقق منهما في StudentTransferWorkflow.
 * آمن للتكرار: يضيف الصلاحية للدور إن لم تكن ممنوحة، ولا يمس أي صلاحية أخرى.
 */
return new class extends Migration
{
    private const ROLE = 'branch_manager';
    private const PERMISSION = 'students.transfer';

    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');
        $permissionId = DB::table('permissions')->where('code', self::PERMISSION)->value('id');

        if (!$roleId || !$permissionId) {
            return;
        }

        $granted = DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->exists();

        if (!$granted) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->value('id');
        $permissionId = DB::table('permissions')->where('code', self::PERMISSION)->value('id');

        if ($roleId && $permissionId) {
            DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->delete();
        }
    }
};
