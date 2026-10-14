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

    /** كتالوج الصلاحيات كما في AcademicSystemSeeder؛ يُضاف الناقص فقط (إضافة صف لا تمنح أي صلاحية بذاتها). */
    private const CATALOG = [
        ['code' => 'branches.view', 'module' => 'branches', 'display_name' => 'عرض الفروع والمقرات'],
        ['code' => 'branches.manage', 'module' => 'branches', 'display_name' => 'إدارة وتحديث بيانات الفروع والمقرات'],
        ['code' => 'windows.view', 'module' => 'windows', 'display_name' => 'عرض النوافذ والتقويم الأكاديمي'],
        ['code' => 'windows.manage', 'module' => 'windows', 'display_name' => 'ضبط النوافذ الزمنية والأعوام الدراسية'],
        ['code' => 'windows.grant_exception', 'module' => 'windows', 'display_name' => 'منح استثناء زمني لفرع'],
        ['code' => 'students.view', 'module' => 'students', 'display_name' => 'عرض ملفات وسجلات الطلاب'],
        ['code' => 'students.create', 'module' => 'students', 'display_name' => 'تسجيل وتعديل بيانات الطلاب'],
        ['code' => 'students.submit_hq', 'module' => 'students', 'display_name' => 'رفع ملف الطالب للاعتماد المركزي'],
        ['code' => 'students.approve_hq', 'module' => 'students', 'display_name' => 'الاعتماد النهائي لملف الطالب وتوليد الرقم الأكاديمي'],
        ['code' => 'students.transfer', 'module' => 'students', 'display_name' => 'طلب واعتماد نقل طالب بين الفروع'],
        ['code' => 'students.archive', 'module' => 'students', 'display_name' => 'أرشفة واسترجاع ملفات الطلاب'],
        ['code' => 'students.delete', 'module' => 'students', 'display_name' => 'حذف سجلات الطلاب نهائياً'],
        ['code' => 'attendance.view', 'module' => 'attendance', 'display_name' => 'عرض كشوف وإحصائيات الحضور والغياب'],
        ['code' => 'attendance.record', 'module' => 'attendance', 'display_name' => 'رصد وتسجيل الحضور والانصراف والمسح الذكي'],
        ['code' => 'attendance.reports', 'module' => 'attendance', 'display_name' => 'إصدار تقارير وإنذارات الحرمان من الغياب'],
        ['code' => 'grades.view', 'module' => 'grades', 'display_name' => 'عرض كشوفات ومحاضر الدرجات'],
        ['code' => 'grades.enter_coursework', 'module' => 'grades', 'display_name' => 'رصد أعمال السنة والتطبيقات التحريرية'],
        ['code' => 'grades.enter_final', 'module' => 'grades', 'display_name' => 'رصد درجات الامتحانات والنهائي والدور الثاني'],
        ['code' => 'grades.submit_batch', 'module' => 'grades', 'display_name' => 'رفع دفعة الكنترول للاعتماد المركزي'],
        ['code' => 'grades.approve_hq', 'module' => 'grades', 'display_name' => 'الاعتماد المركزي لدرجات الفرع وتوليد الختم المشفر'],
        ['code' => 'grades.lock_archive', 'module' => 'grades', 'display_name' => 'الإغلاق الأكاديمي النهائي والأرشفة الرقمية'],
        ['code' => 'curriculum.view', 'module' => 'curriculum', 'display_name' => 'استعراض المناهج والمقررات والهيكل الأكاديمي'],
        ['code' => 'curriculum.manage', 'module' => 'curriculum', 'display_name' => 'إدارة المناهج والخطط والمراحل والشعب الدراسية'],
        ['code' => 'audit.view', 'module' => 'audit', 'display_name' => 'مراقبة سجل التدقيق الجنائي للعمليات والدرجات'],
        ['code' => 'reports.print_official', 'module' => 'reports', 'display_name' => 'طباعة الشهادات والمصدقات والمحررات الرسمية'],
        ['code' => 'users.view', 'module' => 'security', 'display_name' => 'استعراض سجلات المستخدمين وحساباتهم'],
        ['code' => 'users.manage', 'module' => 'security', 'display_name' => 'إدارة وإنشاء وتعديل حسابات المستخدمين'],
        ['code' => 'MANAGE_ROLES', 'module' => 'security', 'display_name' => 'إدارة صلاحيات الأدوار ومصفوفة الصلاحيات'],
        ['code' => 'MANAGE_USER_PERMISSIONS', 'module' => 'security', 'display_name' => 'إدارة الصلاحيات الاستثنائية للمستخدمين'],
        ['code' => 'CHANGE_STUDENT_STATUS', 'module' => 'security', 'display_name' => 'تغيير حالة الطالب (فصل / تعليق / تخرج / نقل)'],
        ['code' => 'APPROVE_STUDENT_DATA', 'module' => 'security', 'display_name' => 'اعتماد وفك اعتماد بيانات الطالب'],
        ['code' => 'admin_settings.view', 'module' => 'settings', 'display_name' => 'استعراض الإعدادات الإدارية والهيكل التنظيمي'],
        ['code' => 'admin_settings.manage', 'module' => 'settings', 'display_name' => 'تعديل الإعدادات الإدارية والتوقيعات الرسمية'],
        ['code' => 'system.monitor', 'module' => 'system', 'display_name' => 'مراقبة أخطاء النظام والصحة التشغيلية'],
        ['code' => 'backups.manage', 'module' => 'system', 'display_name' => 'إدارة وإنشاء واسترجاع النسخ الاحتياطية المشفرة'],
    ];

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
        $now = now();
        foreach (self::CATALOG as $permission) {
            $exists = DB::table('permissions')->where('code', $permission['code'])->exists();
            if (!$exists) {
                DB::table('permissions')->insert($permission + ['created_at' => $now, 'updated_at' => $now]);
            }
        }

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
