<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Branch;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\StudyYear;
use App\Models\Department;
use App\Models\Course;
use App\Models\OperationalWindow;
use Carbon\Carbon;

class AcademicSystemSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles based on the official administrative structure (الباب الأول)
        $rolesData = [
            // الإدارة العليا ومكاتب الإشراف والرقابة المباشرة
            ['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'صلاحيات عليا وإشراف شامل على كافة مكاتب وفروع المعهد التخصصي'],
            ['name' => 'hq_director_office', 'display_name' => 'رئيس مكتب المدير', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'متابعة التوجيهات والمراسلات الإدارية الرسمية للإدارة العامة'],
            ['name' => 'hq_internal_auditor', 'display_name' => 'مكتب المراجعة الداخلية', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'التدقيق المالي والإداري وسجلات الدرجات والأثر الجنائي'],
            ['name' => 'hq_legal_office', 'display_name' => 'مكتب الشؤون القانونية', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'متابعة اللوائح وتفسير القرارات والتحقيقات الإدارية'],
            ['name' => 'hq_media_office', 'display_name' => 'المكتب الإعلامي', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'إدارة البوابة الإعلامية ونشر الإعلانات والأخبار الرسمية'],
            ['name' => 'hq_monitoring_eval', 'display_name' => 'مكتب المتابعة وتقييم الأداء', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'متابعة مؤشرات الإنجاز وتقييم أداء الفروع والمكاتب'],
            ['name' => 'hq_it_office', 'display_name' => 'مكتب تقنية المعلومات', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'إدارة المنظومة وقواعد البيانات والبنية التحتية والربط الشبكي'],
            ['name' => 'hq_public_relations', 'display_name' => 'مكتب العلاقات العامة', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'التنسيق والاتصال المؤسسي مع وزارة الأوقاف والجهات المعنية'],
            
            // مكتب الشؤون الإدارية والمالية
            ['name' => 'hq_admin_financial', 'display_name' => 'مدير مكتب الشؤون الإدارية والمالية', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'إشراف على شؤون الموظفين والمخازن والحسابات والخزينة'],
            
            // مكتب الشؤون التعليمية
            ['name' => 'hq_educational_affairs', 'display_name' => 'مدير مكتب الشؤون التعليمية', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'إشراف على الدراسة والامتحانات والمناهج والتوجيه التربوي'],
            ['name' => 'hq_exams_director', 'display_name' => 'رئيس قسم الدراسة والامتحانات (الإدارة المركزية)', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'إشراف على التقويم واعتماد درجات الفروع والإغلاق الأكاديمي والكنترول المركزي'],
            ['name' => 'hq_curriculum_office', 'display_name' => 'رئيس قسم المناهج والمقررات', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'إدارة المقررات الدراسية والأوعية الزمنية والخطط التعليمية'],
            ['name' => 'hq_educational_guidance', 'display_name' => 'رئيس قسم التوجيه التربوي', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'متابعة جودة التدريس والتقويم التربوي للفروع'],
            ['name' => 'hq_student_affairs', 'display_name' => 'رئيس قسم شؤون الطلبة (الإدارة المركزية)', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'تدقيق شروط القبول والاعتماد النهائي وتوليد الأرقام الأكاديمية والشهادات'],

            // مكتب المعلومات والتوثيق
            ['name' => 'hq_documentation_info', 'display_name' => 'مدير مكتب المعلومات والتوثيق', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'إشراف على وحدات القبول، المنظومة، التوثيق، النتائج، والمراجعة'],

            // إدارة الفروع والمعاهد
            ['name' => 'branches_director', 'display_name' => 'مدير إدارة الفروع والمعاهد', 'scope_type' => 'GLOBAL_SCOPE', 'description' => 'الإشراف المركزي ومتابعة الأداء التشغيلي لكافة فروع المعهد'],
            ['name' => 'branch_manager', 'display_name' => 'مدير المعهد (الفرع)', 'scope_type' => 'BRANCH_SCOPE', 'description' => 'إدارة الفرع ومتابعة المؤشرات والعمليات ورفع الدفعات للاعتماد المركزي'],
            ['name' => 'branch_admin_fin', 'display_name' => 'قسم الشؤون الإدارية والمالية بالفرع', 'scope_type' => 'BRANCH_SCOPE', 'description' => 'إدارة الكادر والصيانة والاحتياجات المالية بالفرع'],
            ['name' => 'branch_exams_officer', 'display_name' => 'قسم الدراسة والامتحانات بالفرع', 'scope_type' => 'BRANCH_SCOPE', 'description' => 'رصد درجات أعمال السنة والامتحانات وإعداد كشوف الدرجات والكنترول بالفرع'],
            ['name' => 'branch_registrar', 'display_name' => 'قسم شؤون الطلبة بالفرع', 'scope_type' => 'BRANCH_SCOPE', 'description' => 'تسجيل الطلاب الجدد وتدقيق السن والمستندات ومتابعة القيد'],
            ['name' => 'branch_social_worker', 'display_name' => 'أخصائي اجتماعي بالفرع', 'scope_type' => 'BRANCH_SCOPE', 'description' => 'المتابعة السلوكية والتربوية والدعم النفسي للطلبة بالفرع'],
            ['name' => 'course_instructor', 'display_name' => 'عضو هيئة تدريس (أستاذ مقرر)', 'scope_type' => 'BRANCH_SCOPE', 'description' => 'رصد درجات الأنشطة والتطبيقات والاختبارات النصفية للمقرر المسند إليه'],
        ];

        $roles = [];
        foreach ($rolesData as $r) {
            $roles[$r['name']] = Role::create($r);
        }

        // 2. Permissions
        $permissionsData = [
            // Branches
            ['code' => 'branches.view', 'module' => 'branches', 'display_name' => 'عرض الفروع والمقرات'],
            ['code' => 'branches.manage', 'module' => 'branches', 'display_name' => 'إدارة وتحديث بيانات الفروع'],
            // Windows
            ['code' => 'windows.view', 'module' => 'windows', 'display_name' => 'عرض النوافذ والتقويم الأكاديمي'],
            ['code' => 'windows.manage', 'module' => 'windows', 'display_name' => 'ضبط النوافذ الزمنية المركزية'],
            ['code' => 'windows.grant_exception', 'module' => 'windows', 'display_name' => 'منح استثناء زمني لفرع'],
            // Students
            ['code' => 'students.view', 'module' => 'students', 'display_name' => 'عرض ملفات وسجلات الطلاب'],
            ['code' => 'students.create', 'module' => 'students', 'display_name' => 'تسجيل طالب جديد بالفرع'],
            ['code' => 'students.submit_hq', 'module' => 'students', 'display_name' => 'رفع ملف الطالب للاعتماد المركزي'],
            ['code' => 'students.approve_hq', 'module' => 'students', 'display_name' => 'الاعتماد النهائي لملف الطالب وتوليد الرقم الأكاديمي'],
            ['code' => 'students.transfer', 'module' => 'students', 'display_name' => 'طلب واعتماد نقل طالب بين الفروع'],
            // Grades & Control
            ['code' => 'grades.view', 'module' => 'grades', 'display_name' => 'عرض كشوفات ومحاضر الدرجات'],
            ['code' => 'grades.enter_coursework', 'module' => 'grades', 'display_name' => 'رصد أعمال السنة والتطبيقات التحريرية'],
            ['code' => 'grades.enter_final', 'module' => 'grades', 'display_name' => 'رصد درجات الامتحانات والنهائي والدور الثاني'],
            ['code' => 'grades.submit_batch', 'module' => 'grades', 'display_name' => 'رفع دفعة الكنترول للاعتماد المركزي'],
            ['code' => 'grades.approve_hq', 'module' => 'grades', 'display_name' => 'الاعتماد المركزي لدرجات الفرع وتوليد الختم المشفر'],
            ['code' => 'grades.lock_archive', 'module' => 'grades', 'display_name' => 'الإغلاق الأكاديمي النهائي والأرشفة الرقمية'],
            // Audit & Reports
            ['code' => 'audit.view', 'module' => 'audit', 'display_name' => 'مراقبة سجل التدقيق الجنائي للدرجات'],
            ['code' => 'reports.print_official', 'module' => 'reports', 'display_name' => 'طباعة الصحائف والشهادات الرسمية'],
            // =========================================================
            // Security Permissions (CRIT-1 — required for authorization layer)
            // =========================================================
            ['code' => 'MANAGE_ROLES', 'module' => 'security', 'display_name' => 'إدارة صلاحيات الأدوار'],
            ['code' => 'MANAGE_USER_PERMISSIONS', 'module' => 'security', 'display_name' => 'إدارة الصلاحيات الاستثنائية للمستخدمين'],
            ['code' => 'CHANGE_STUDENT_STATUS', 'module' => 'security', 'display_name' => 'تغيير حالة الطالب (فصل / تعليق / تخرج / نقل)'],
            ['code' => 'APPROVE_STUDENT_DATA', 'module' => 'security', 'display_name' => 'اعتماد وفك اعتماد بيانات الطالب'],
        ];

        $permissions = [];
        foreach ($permissionsData as $p) {
            $permissions[$p['code']] = Permission::create($p);
        }

        // Attach permissions to roles
        $roles['super_admin']->permissions()->sync(array_values(array_map(fn($p) => $p->id, $permissions)));

        $roles['hq_exams_director']->permissions()->sync([
            $permissions['branches.view']->id,
            $permissions['windows.view']->id,
            $permissions['windows.manage']->id,
            $permissions['students.view']->id,
            $permissions['grades.view']->id,
            $permissions['grades.approve_hq']->id,
            $permissions['grades.lock_archive']->id,
            $permissions['audit.view']->id,
            $permissions['reports.print_official']->id,
        ]);

        $roles['hq_student_affairs']->permissions()->sync([
            $permissions['branches.view']->id,
            $permissions['students.view']->id,
            $permissions['students.approve_hq']->id,
            $permissions['students.transfer']->id,
            $permissions['reports.print_official']->id,
            // Security permissions for student affairs
            $permissions['CHANGE_STUDENT_STATUS']->id,
            $permissions['APPROVE_STUDENT_DATA']->id,
        ]);

        // hq_it_office manages roles and user permissions
        $roles['hq_it_office']->permissions()->sync([
            $permissions['MANAGE_ROLES']->id,
            $permissions['MANAGE_USER_PERMISSIONS']->id,
        ]);

        $roles['branch_manager']->permissions()->sync([
            $permissions['students.view']->id,
            $permissions['students.create']->id,
            $permissions['students.submit_hq']->id,
            $permissions['grades.view']->id,
            $permissions['grades.enter_coursework']->id,
            $permissions['grades.enter_final']->id,
            $permissions['grades.submit_batch']->id,
            $permissions['reports.print_official']->id,
        ]);

        $roles['branch_exams_officer']->permissions()->sync([
            $permissions['students.view']->id,
            $permissions['grades.view']->id,
            $permissions['grades.enter_coursework']->id,
            $permissions['grades.enter_final']->id,
            $permissions['grades.submit_batch']->id,
        ]);

        $roles['branch_registrar']->permissions()->sync([
            $permissions['students.view']->id,
            $permissions['students.create']->id,
            $permissions['students.submit_hq']->id,
        ]);

        // 3. Branches
        $branchesData = [
            ['code' => '01', 'name' => 'فرع طرابلس المركزي', 'city' => 'طرابلس', 'address' => 'طريق الشط', 'phone' => '021-3344551', 'email' => 'tripoli@iiis.sch.ly'],
            ['code' => '02', 'name' => 'فرع بنغازي', 'city' => 'بنغازي', 'address' => 'حي السلام', 'phone' => '061-2233441', 'email' => 'benghazi@iiis.sch.ly'],
            ['code' => '03', 'name' => 'فرع مصراتة', 'city' => 'مصراتة', 'address' => 'شارع طرابلس', 'phone' => '051-5566771', 'email' => 'misrata@iiis.sch.ly'],
            ['code' => '04', 'name' => 'فرع سرت', 'city' => 'سرت', 'address' => 'الشارع الرئيسي', 'phone' => '054-6677881', 'email' => 'sert@iiis.sch.ly'],
            ['code' => '05', 'name' => 'فرع الزاوية', 'city' => 'الزاوية', 'address' => 'الميدان العام', 'phone' => '023-4455661', 'email' => 'zawiya@iiis.sch.ly'],
            ['code' => '06', 'name' => 'فرع البيضاء', 'city' => 'البيضاء', 'address' => 'شارع العروبة', 'phone' => '067-1122334', 'email' => 'baida@iiis.sch.ly'],
            ['code' => '07', 'name' => 'فرع طبرق', 'city' => 'طبرق', 'address' => 'الميناء', 'phone' => '087-2233445', 'email' => 'tobruk@iiis.sch.ly'],
            ['code' => '08', 'name' => 'فرع سبها', 'city' => 'سبها', 'address' => 'المنشية', 'phone' => '071-3344556', 'email' => 'sebha@iiis.sch.ly'],
        ];

        $branches = [];
        foreach ($branchesData as $b) {
            $branches[$b['code']] = Branch::create($b);
        }

        // 4. Academic Structure
        $currentYear = AcademicYear::create([
            'code' => '2026-2027',
            'name' => '1448هـ الموافق 2026/2027م',
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_current' => true,
            'is_locked' => false,
        ]);

        $studyYears = [
            1 => StudyYear::create(['name' => 'السنة الأولى (التأسيسية)', 'level_order' => 1, 'description' => 'المرحلة الدراسية الأولى - نظام فصلي']),
            2 => StudyYear::create(['name' => 'السنة الثانية (المتوسطة)', 'level_order' => 2, 'description' => 'المرحلة الدراسية الثانية - نظام فصلي']),
            3 => StudyYear::create(['name' => 'السنة الثالثة (شهادة إتمام المرحلة)', 'level_order' => 3, 'description' => 'مرحلة التخرج والشهادة التخصصية - نظام فترتين وامتحان نهائي']),
        ];

        // Official Department (شعبة الدعوة وأصول الدين)
        $deptDawah = Department::create([
            'code' => 'DAWAH_USUL_ALDIN',
            'name' => 'شعبة الدعوة وأصول الدين',
            'description' => 'الخطة والمقررات المعتمدة من وزارة الأوقاف والشؤون الإسلامية - الحكومة الليبية',
            'is_active' => true,
        ]);

        // 5. Official Curriculum (الباب الثاني - المقررات الدراسية الـ 12 للسنوات الثلاث)

        // السنة الأولى: نظام فصلي
        $year1Courses = [
            ['code' => 'QRN101', 'name' => 'القرآن وأحكام التجويد', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'TAF101', 'name' => 'التفسير', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'AQD101', 'name' => 'العقيدة', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'FQH101', 'name' => 'الفقه', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'USL101', 'name' => 'أصول الفقه', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'HAD101', 'name' => 'علوم الحديث', 'hours' => 1, 'max' => 40.00, 'pass' => 20.00, 'resit' => 26.00],
            ['code' => 'DAW101', 'name' => 'منهج الدعوة', 'hours' => 1, 'max' => 40.00, 'pass' => 20.00, 'resit' => 26.00],
            ['code' => 'HIS101', 'name' => 'التاريخ (السيرة النبوية)', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'LNG101', 'name' => 'الدراسات اللغوية', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'LIT101', 'name' => 'الدراسات الأدبية', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'CMP101', 'name' => 'الحاسوب', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'ENG101', 'name' => 'اللغة الإنجليزية', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
        ];

        foreach ($year1Courses as $c) {
            $isSingle = ($c['hours'] === 1);
            Course::create([
                'study_year_id' => $studyYears[1]->id,
                'department_id' => $deptDawah->id,
                'semester' => 1,
                'code' => $c['code'],
                'name' => $c['name'],
                'credit_hours' => $c['hours'],
                'weekly_hours' => $c['hours'],
                'assessment_system' => 'SEMESTER_SYSTEM',
                'max_coursework_grade' => $isSingle ? 6.00 : 12.00,
                'max_midterm_grade' => $isSingle ? 2.00 : 4.00,
                'max_final_grade' => $isSingle ? 14.00 : 28.00,
                'pass_grade' => $isSingle ? 20.00 : 40.00,
                'max_score' => $c['max'],
                'pass_min_score' => $c['pass'],
                'second_round_max' => $c['resit'],
                'is_active' => true,
            ]);
        }

        // السنة الثانية: نظام فصلي
        $year2Courses = [
            ['code' => 'QRN201', 'name' => 'القرآن وأحكام التجويد', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'TAF201', 'name' => 'التفسير', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'AQD201', 'name' => 'العقيدة', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'FQH201', 'name' => 'الفقه', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'USL201', 'name' => 'أصول الفقه', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'HAD201', 'name' => 'علوم الحديث', 'hours' => 1, 'max' => 40.00, 'pass' => 20.00, 'resit' => 28.00],
            ['code' => 'DAW201', 'name' => 'منهج الدعوة', 'hours' => 1, 'max' => 40.00, 'pass' => 20.00, 'resit' => 28.00],
            ['code' => 'HIS201', 'name' => 'التاريخ (عصر الخلفاء الراشدين)', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'LNG201', 'name' => 'الدراسات اللغوية', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'LIT201', 'name' => 'الدراسات الأدبية', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'CMP201', 'name' => 'الحاسوب', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
            ['code' => 'ENG201', 'name' => 'اللغة الإنجليزية', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 56.00],
        ];

        foreach ($year2Courses as $c) {
            $isSingle = ($c['hours'] === 1);
            Course::create([
                'study_year_id' => $studyYears[2]->id,
                'department_id' => $deptDawah->id,
                'semester' => 1,
                'code' => $c['code'],
                'name' => $c['name'],
                'credit_hours' => $c['hours'],
                'weekly_hours' => $c['hours'],
                'assessment_system' => 'SEMESTER_SYSTEM',
                'max_coursework_grade' => $isSingle ? 6.00 : 12.00,
                'max_midterm_grade' => $isSingle ? 2.00 : 4.00,
                'max_final_grade' => $isSingle ? 14.00 : 28.00,
                'pass_grade' => $isSingle ? 20.00 : 40.00,
                'max_score' => $c['max'],
                'pass_min_score' => $c['pass'],
                'second_round_max' => $c['resit'],
                'is_active' => true,
            ]);
        }

        // السنة الثالثة: سنة التخرج وشهادة إتمام المرحلة (نظام فترتين وامتحان نهائي)
        $year3Courses = [
            ['code' => 'QRN301', 'name' => 'القرآن وأحكام التجويد', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 48.00],
            ['code' => 'TAF301', 'name' => 'التفسير', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 48.00],
            ['code' => 'AQD301', 'name' => 'العقيدة', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 48.00],
            ['code' => 'FQH301', 'name' => 'الفقه', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 48.00],
            ['code' => 'USL301', 'name' => 'أصول الفقه', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 48.00],
            ['code' => 'HAD301', 'name' => 'علوم الحديث', 'hours' => 1, 'max' => 40.00, 'pass' => 20.00, 'resit' => 24.00],
            ['code' => 'DAW301', 'name' => 'منهج الدعوة', 'hours' => 1, 'max' => 40.00, 'pass' => 20.00, 'resit' => 24.00],
            ['code' => 'HIS301', 'name' => 'التاريخ (الخلافة الأموية والعباسية)', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 48.00],
            ['code' => 'LNG301', 'name' => 'الدراسات اللغوية', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 48.00],
            ['code' => 'LIT301', 'name' => 'الدراسات الأدبية', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 48.00],
            ['code' => 'CMP301', 'name' => 'الحاسوب', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 48.00],
            ['code' => 'ENG301', 'name' => 'اللغة الإنجليزية', 'hours' => 2, 'max' => 80.00, 'pass' => 40.00, 'resit' => 48.00],
        ];

        foreach ($year3Courses as $c) {
            $isSingle = ($c['hours'] === 1);
            Course::create([
                'study_year_id' => $studyYears[3]->id,
                'department_id' => $deptDawah->id,
                'semester' => 1,
                'code' => $c['code'],
                'name' => $c['name'],
                'credit_hours' => $c['hours'],
                'weekly_hours' => $c['hours'],
                'assessment_system' => 'ANNUAL_PERIODS_SYSTEM',
                'max_coursework_grade' => $isSingle ? 5.00 : 10.00, // أعمال الفترة
                'max_midterm_grade' => $isSingle ? 3.00 : 6.00,     // امتحان الفترة
                'max_final_grade' => $isSingle ? 24.00 : 48.00,     // امتحان نهاية العام
                'pass_grade' => $isSingle ? 20.00 : 40.00,
                'max_score' => $c['max'],
                'pass_min_score' => $c['pass'],
                'second_round_max' => $c['resit'],
                'is_active' => true,
            ]);
        }

        // 6. Users representing the official administrative roles
        $defaultPassword = Hash::make('Password@2026');

        $usersData = [
            [
                'name' => 'الشيخ الدكتور / المدير العام للمعهد التخصصي',
                'email' => 'admin@iiis.sch.ly',
                'role_id' => $roles['super_admin']->id,
                'branch_id' => null,
                'national_id' => '119780000001',
                'phone' => '091-0000001',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'الأستاذ / رئيس قسم الدراسة والامتحانات بالإدارة العامة',
                'email' => 'exams.hq@iiis.sch.ly',
                'role_id' => $roles['hq_exams_director']->id,
                'branch_id' => null,
                'national_id' => '119820000002',
                'phone' => '091-0000002',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'الأستاذ / رئيس قسم شؤون الطلبة بالإدارة المركزية',
                'email' => 'students.hq@iiis.sch.ly',
                'role_id' => $roles['hq_student_affairs']->id,
                'branch_id' => null,
                'national_id' => '119850000003',
                'phone' => '091-0000003',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'الأستاذ / مدير مكتب المعلومات والتوثيق والمنظومة',
                'email' => 'documentation@iiis.sch.ly',
                'role_id' => $roles['hq_documentation_info']->id,
                'branch_id' => null,
                'national_id' => '119840000004',
                'phone' => '091-0000004',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'الشيخ / مدير إدارة الفروع والمعاهد الدينية',
                'email' => 'branches.director@iiis.sch.ly',
                'role_id' => $roles['branches_director']->id,
                'branch_id' => null,
                'national_id' => '119790000005',
                'phone' => '091-0000005',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'الشيخ / مدير معهد فرع طرابلس المركزي',
                'email' => 'manager.tip@iiis.sch.ly',
                'role_id' => $roles['branch_manager']->id,
                'branch_id' => $branches['01']->id,
                'national_id' => '119800000011',
                'phone' => '092-0000011',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'الأستاذ / رئيس قسم الدراسة والامتحانات بفرع طرابلس',
                'email' => 'exams.tip@iiis.sch.ly',
                'role_id' => $roles['branch_exams_officer']->id,
                'branch_id' => $branches['01']->id,
                'national_id' => '119880000012',
                'phone' => '092-0000012',
                'password' => $defaultPassword,
            ],
            [
                'name' => 'الأستاذ / رئيس قسم شؤون الطلبة بفرع طرابلس',
                'email' => 'registrar.tip@iiis.sch.ly',
                'role_id' => $roles['branch_registrar']->id,
                'branch_id' => $branches['01']->id,
                'national_id' => '119900000013',
                'phone' => '092-0000013',
                'password' => $defaultPassword,
            ],
        ];

        $createdUsers = [];
        foreach ($usersData as $u) {
            $createdUsers[] = User::create($u);
        }

        // 7. Operational Windows (Active)
        OperationalWindow::create([
            'academic_year_id' => $currentYear->id,
            'window_type' => 'REGISTRATION',
            'title' => 'فترة قبول وتسجيل الطلاب الجدد للعام 2026/2027م - شُعبة الدعوة وأصول الدين',
            'start_at' => Carbon::now()->subDays(5),
            'end_at' => Carbon::now()->addDays(25),
            'is_active' => true,
            'created_by' => $createdUsers[0]->id,
        ]);

        OperationalWindow::create([
            'academic_year_id' => $currentYear->id,
            'window_type' => 'S1_COURSEWORK',
            'title' => 'فترة رصد أعمال السنة والتطبيقات التحريرية والامتحانات النصفية',
            'start_at' => Carbon::now()->subDays(2),
            'end_at' => Carbon::now()->addDays(40),
            'is_active' => true,
            'created_by' => $createdUsers[1]->id,
        ]);

        // 8. Enrolled Students across Study Years
        $studentsData = [
            // السنة الأولى
            [
                'academic_number' => '2026101001',
                'national_id' => '120050011223',
                'branch_id' => $branches['01']->id,
                'department_id' => $deptDawah->id,
                'current_study_year_id' => $studyYears[1]->id,
                'first_name' => 'عمر',
                'father_name' => 'المختار',
                'grandfather_name' => 'أحمد',
                'family_name' => 'الطرابلسي',
                'mother_name' => 'فاطمة صالح',
                'gender' => 'MALE',
                'birth_date' => '2008-04-15',
                'birth_place' => 'طرابلس',
                'nationality' => 'ليبي',
                'phone' => '091-2345671',
                'guardian_phone' => '092-2345671',
                'study_type' => 'REGULAR',
                'academic_status' => 'ENROLLED_ACTIVE',
                'enrolled_academic_year_id' => $currentYear->id,
            ],
            [
                'academic_number' => '2026101002',
                'national_id' => '120050011224',
                'branch_id' => $branches['01']->id,
                'department_id' => $deptDawah->id,
                'current_study_year_id' => $studyYears[1]->id,
                'first_name' => 'عبد الرحمن',
                'father_name' => 'محمد',
                'grandfather_name' => 'علي',
                'family_name' => 'البوسيفي',
                'mother_name' => 'مريم مسعود',
                'gender' => 'MALE',
                'birth_date' => '2008-07-20',
                'birth_place' => 'غريان',
                'nationality' => 'ليبي',
                'phone' => '091-2345672',
                'guardian_phone' => '092-2345672',
                'study_type' => 'REGULAR',
                'academic_status' => 'ENROLLED_ACTIVE',
                'enrolled_academic_year_id' => $currentYear->id,
            ],
            // السنة الثانية
            [
                'academic_number' => '2026102001',
                'national_id' => '120040011225',
                'branch_id' => $branches['01']->id,
                'department_id' => $deptDawah->id,
                'current_study_year_id' => $studyYears[2]->id,
                'first_name' => 'أسامة',
                'father_name' => 'سالم',
                'grandfather_name' => 'مصطفى',
                'family_name' => 'الزوي',
                'mother_name' => 'عائشة نوري',
                'gender' => 'MALE',
                'birth_date' => '2007-03-10',
                'birth_place' => 'بنغازي',
                'nationality' => 'ليبي',
                'phone' => '091-2345673',
                'guardian_phone' => '092-2345673',
                'study_type' => 'REGULAR',
                'academic_status' => 'ENROLLED_ACTIVE',
                'enrolled_academic_year_id' => $currentYear->id,
            ],
            // السنة الثالثة (شهادة إتمام المرحلة)
            [
                'academic_number' => '2026103001',
                'national_id' => '120030011226',
                'branch_id' => $branches['01']->id,
                'department_id' => $deptDawah->id,
                'current_study_year_id' => $studyYears[3]->id,
                'first_name' => 'إبراهيم',
                'father_name' => 'المهدي',
                'grandfather_name' => 'خالد',
                'family_name' => 'الفيتوري',
                'mother_name' => 'زينب محمود',
                'gender' => 'MALE',
                'birth_date' => '2006-09-05',
                'birth_place' => 'مصراتة',
                'nationality' => 'ليبي',
                'phone' => '091-2345674',
                'guardian_phone' => '092-2345674',
                'study_type' => 'REGULAR',
                'academic_status' => 'ENROLLED_ACTIVE',
                'enrolled_academic_year_id' => $currentYear->id,
            ],
            [
                'academic_number' => '2026103002',
                'national_id' => '120030011227',
                'branch_id' => $branches['01']->id,
                'department_id' => $deptDawah->id,
                'current_study_year_id' => $studyYears[3]->id,
                'first_name' => 'صهيب',
                'father_name' => 'طارق',
                'grandfather_name' => 'رمضان',
                'family_name' => 'الورفلي',
                'mother_name' => 'سعاد خليفة',
                'gender' => 'MALE',
                'birth_date' => '2006-11-12',
                'birth_place' => 'طرابلس',
                'nationality' => 'ليبي',
                'phone' => '091-2345675',
                'guardian_phone' => '092-2345675',
                'study_type' => 'REGULAR',
                'academic_status' => 'ENROLLED_ACTIVE',
                'enrolled_academic_year_id' => $currentYear->id,
            ],
        ];

        foreach ($studentsData as $st) {
            \App\Models\Student::create($st);
        }
    }
}
