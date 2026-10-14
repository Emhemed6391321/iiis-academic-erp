<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * المدير العام (super_admin) يجب أن يملك كل وحدات المنظومة وصلاحياتها.
 *
 * الخلل: الدور أُسنِدت له الصلاحيات عند الزراعة الأولى فقط، وأضيفت صلاحيات لاحقة
 * (وأهمها permissions.manage التي تحمي مسار مصفوفة الصلاحيات) دون إسنادها إلى الدور،
 * فظهرت مصفوفة الصلاحيات بخانات غير مفعّلة للمدير العام رغم أنه يتجاوز الفحص برمجياً.
 *
 * هذا الترحيل يُنشئ صلاحية permissions.manage إن لم تكن موجودة، ثم يُسند إلى
 * super_admin كل الصلاحيات الموجودة. آمن للتكرار لأنه لا يُكرر الإسنادات.
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'super_admin')->first();
        if (!$role) {
            return;
        }

        $now = now();

        if (!DB::table('permissions')->where('code', 'permissions.manage')->exists()) {
            DB::table('permissions')->insert([
                'code' => 'permissions.manage',
                'module' => 'security',
                'display_name' => 'إدارة مصفوفة الصلاحيات وتفعيل الوحدات',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $attached = DB::table('role_permissions')
            ->where('role_id', $role->id)
            ->pluck('permission_id')
            ->all();

        $missing = DB::table('permissions')
            ->whereNotIn('id', $attached)
            ->pluck('id');

        if ($missing->isNotEmpty()) {
            DB::table('role_permissions')->insert(
                $missing->map(fn ($permissionId) => [
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        // لا تراجع: إسناد كل الصلاحيات للمدير العام هو الحالة المطلوبة.
    }
};
