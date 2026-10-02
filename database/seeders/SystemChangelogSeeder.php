<?php

namespace Database\Seeders;

use App\Models\SystemChangelog;
use Illuminate\Database\Seeder;

class SystemChangelogSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            [
                'version' => '1.0.0',
                'title' => 'الإطلاق الرسمي للمنظومة الأكاديمية (ERP v1.0)',
                'description' => 'الإطلاق الأول للنظام الأكاديمي المتكامل للمعهد التخصصي للدراسات الإسلامية. يشمل: نظام القيد والتسجيل، إدارة الفروع والأقسام، وإدارة المستخدمين والصلاحيات.',
                'type' => 'feature',
                'impact' => 'critical',
                'author' => 'فريق التطوير',
                'branch' => 'main',
                'affected_modules' => ['auth', 'students', 'branches', 'users'],
                'tags' => ['إطلاق', 'قاعدة البيانات', 'مصادقة'],
                'deployed_at' => '2025-09-01 08:00:00',
            ],
            [
                'version' => '1.1.0',
                'title' => 'نظام حضور وانصراف الطلاب المركزي',
                'description' => 'إضافة وحدة كاملة لتسجيل وإدارة حضور وانصراف الطلاب، مع احتساب نسب الغياب التلقائي وإصدار التحذيرات عند تجاوز الحد المسموح به (25%).',
                'type' => 'feature',
                'impact' => 'high',
                'author' => 'فريق التطوير',
                'branch' => 'feature/attendance',
                'affected_modules' => ['attendance', 'students'],
                'tags' => ['حضور', 'غياب', 'تقارير'],
                'deployed_at' => '2025-10-15 10:00:00',
            ],
            [
                'version' => '1.2.0',
                'title' => 'إدارة الجدول الأكاديمي والمقررات الدراسية',
                'description' => 'إضافة نظام إدارة المناهج والمقررات الدراسية، مع إمكانية تعيين المواد للفصول الدراسية وتوليد الجداول الأسبوعية، ومتابعة توزيع الأستاذة على المواد.',
                'type' => 'feature',
                'impact' => 'high',
                'author' => 'فريق التطوير',
                'branch' => 'feature/curriculum',
                'affected_modules' => ['curriculum', 'courses', 'departments'],
                'tags' => ['مناهج', 'جدول دراسي', 'أقسام'],
                'deployed_at' => '2025-11-20 09:00:00',
            ],
            [
                'version' => '1.3.0',
                'title' => 'نظام إدارة الامتحانات والنتائج',
                'description' => 'إطلاق وحدة الامتحانات الشاملة: رصد الدرجات، احتساب المعدل التراكمي، توليد كشوفات النتائج الرسمية، وإصدار الشهادات والإفادات للطلاب.',
                'type' => 'feature',
                'impact' => 'high',
                'author' => 'فريق التطوير',
                'branch' => 'feature/exams',
                'affected_modules' => ['exams', 'grades', 'results', 'certificates'],
                'tags' => ['امتحانات', 'نتائج', 'شهادات'],
                'deployed_at' => '2026-01-10 08:30:00',
            ],
            [
                'version' => '1.4.0',
                'title' => 'نظام سجل المراسلات والوثائق الرسمية',
                'description' => 'إضافة وحدة إدارة المراسلات الإدارية الواردة والصادرة، مع إمكانية رفع المرفقات وتتبع حالة كل مراسلة وتوليد أرقام المراسلة التسلسلية.',
                'type' => 'feature',
                'impact' => 'medium',
                'author' => 'فريق التطوير',
                'branch' => 'feature/correspondence',
                'affected_modules' => ['correspondence', 'documents'],
                'tags' => ['مراسلات', 'وثائق', 'أرشيف'],
                'deployed_at' => '2026-02-14 10:00:00',
            ],
            [
                'version' => '1.5.0',
                'title' => 'تحسينات الأمان ونظام التدقيق المركزي',
                'description' => 'تطبيق سجل التدقيق المركزي (Audit Trail) بتقنية Hash Chain لمنع التلاعب بالبيانات، إضافة تتبع جلسات تسجيل الدخول، وتشفير البيانات الحساسة.',
                'type' => 'security',
                'impact' => 'critical',
                'author' => 'فريق أمن المعلومات',
                'branch' => 'security/audit-trail',
                'affected_modules' => ['auth', 'audit', 'security'],
                'tags' => ['أمان', 'تدقيق', 'Hash Chain', 'تشفير'],
                'deployed_at' => '2026-03-01 08:00:00',
            ],
            [
                'version' => '2.0.0',
                'title' => 'إطلاق ERP v2.0 — واجهة مستخدم كاملة بتقنية Alpine.js',
                'description' => 'إعادة بناء الواجهة الأمامية بالكامل باستخدام Alpine.js ضمن تطبيق Blade موحد، مع دعم الوضع الليلي (Dark Mode)، تصميم متجاوب لجميع الشاشات، وأداء محسّن يقلل من الطلبات للخادم.',
                'type' => 'ui',
                'impact' => 'critical',
                'author' => 'فريق التطوير',
                'branch' => 'release/v2.0',
                'affected_modules' => ['dashboard', 'auth', 'all'],
                'tags' => ['Alpine.js', 'واجهة', 'Dark Mode', 'UX'],
                'deployed_at' => '2026-05-01 09:00:00',
            ],
            [
                'version' => '2.1.0',
                'title' => 'نظام الرواتب والحوافز والاستحقاقات المالية',
                'description' => 'إضافة وحدة الرواتب الشاملة: تعريف الدرجات الوظيفية، احتساب الأساسي والبدلات والحسميات التلقائية (تأمينات، غياب)، توليد كشوفات الرواتب PDF وإصدار تقارير الدفعيات الشهرية.',
                'type' => 'feature',
                'impact' => 'high',
                'author' => 'فريق التطوير',
                'branch' => 'feature/payroll',
                'affected_modules' => ['payroll', 'staff', 'hr'],
                'tags' => ['رواتب', 'مالية', 'PDF', 'HR'],
                'deployed_at' => '2026-06-15 10:00:00',
            ],
            [
                'version' => '2.2.0',
                'title' => 'وحدة إصدار بطاقات الطلاب (A4 / PVC)',
                'description' => 'إضافة إمكانية توليد وطباعة بطاقات الهوية الطلابية المعتمدة بتصميمَي A4 والبطاقة البلاستيكية PVC، مع الرمز الشريطي وصورة الطالب وبيانات الفرع.',
                'type' => 'feature',
                'impact' => 'medium',
                'author' => 'فريق التطوير',
                'branch' => 'feature/student-cards',
                'affected_modules' => ['students', 'printing'],
                'tags' => ['بطاقات', 'طباعة', 'QR'],
                'deployed_at' => '2026-07-01 08:00:00',
            ],
            [
                'version' => '2.3.0',
                'title' => 'لوحة مراقبة صحة النظام وسجل الأخطاء',
                'description' => 'إضافة لوحة مراقبة تقنية مركزية تعرض: مقاييس أداء الخادم (CPU، ذاكرة)، حالة قاعدة البيانات، سجل أخطاء النظام المصنفة بالخطورة، وتنبيهات آنية عند حدوث أعطال.',
                'type' => 'performance',
                'impact' => 'high',
                'author' => 'فريق البنية التحتية',
                'branch' => 'feature/system-monitoring',
                'affected_modules' => ['system_health', 'monitoring', 'error_logs'],
                'tags' => ['مراقبة', 'أداء', 'سجلات', 'DevOps'],
                'deployed_at' => '2026-08-10 09:00:00',
            ],
            [
                'version' => '2.3.1',
                'title' => 'إصلاح خطأ احتساب الغياب في نهاية الفصل الدراسي',
                'description' => 'إصلاح خلل في محرك احتساب الغياب كان يتجاهل أيام العطل الرسمية المحددة في التقويم الأكاديمي عند احتساب نسبة غياب الطلاب، مما كان يتسبب في تحذيرات خاطئة.',
                'type' => 'fix',
                'impact' => 'high',
                'author' => 'فريق التطوير',
                'branch' => 'hotfix/absence-calc',
                'affected_modules' => ['attendance', 'academic_calendar'],
                'tags' => ['إصلاح', 'غياب', 'تقويم'],
                'requires_migration' => false,
                'deployed_at' => '2026-08-25 11:00:00',
            ],
            [
                'version' => '2.4.0',
                'title' => 'استيراد دفعات الطلاب عبر ملفات CSV',
                'description' => 'إضافة وحدة استيراد دفعات الطلاب الجديدة دفعة واحدة عبر رفع ملف CSV معتمد، مع التحقق التلقائي من صحة البيانات (الرقم الوطني 12 خانة، الحد الأدنى للعمر 15 سنة)، توليد أرقام القيد الأكاديمي آلياً، وعرض تقرير مفصل بالسجلات المقيدة والمرفوضة.',
                'type' => 'feature',
                'impact' => 'high',
                'author' => 'فريق التطوير',
                'branch' => 'feature/batch-import',
                'affected_modules' => ['students', 'registration', 'import'],
                'tags' => ['استيراد', 'CSV', 'دفعات', 'ميزة جديدة'],
                'requires_migration' => false,
                'deployed_at' => '2026-10-01 20:00:00',
            ],
            [
                'version' => '2.4.1',
                'title' => 'تعزيز الملف الشخصي بسجل النشاط والمصادقة الثنائية',
                'description' => 'تحديث صفحة الملف الشخصي لعرض آخر 8 نشاطات للمستخدم، صلاحياته الممنوحة، آخر IP لتسجيل الدخول، وإضافة زر تفعيل/تعطيل المصادقة الثنائية (2FA) مع تسجيل كل تغيير في سجل التدقيق.',
                'type' => 'security',
                'impact' => 'medium',
                'author' => 'فريق التطوير',
                'branch' => 'feature/profile-2fa',
                'affected_modules' => ['profile', 'security', 'audit'],
                'tags' => ['2FA', 'ملف شخصي', 'أمان', 'سجل نشاط'],
                'requires_migration' => false,
                'deployed_at' => '2026-10-02 00:00:00',
            ],
        ];

        foreach ($entries as $entry) {
            SystemChangelog::updateOrCreate(
                ['version' => $entry['version'], 'title' => $entry['title']],
                array_merge($entry, ['is_published' => true])
            );
        }

        $this->command->info('✅ تم زرع ' . count($entries) . ' سجل تحديث حقيقي بنجاح.');
    }
}
