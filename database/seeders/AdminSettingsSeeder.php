<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdministrativeSetting;
use App\Models\OrganizationalUnit;
use App\Models\JobPosition;
use App\Models\EmployeePlacement;
use App\Models\OfficialDocumentSignatory;
use App\Models\User;
use App\Models\Branch;
use App\Services\AdminSettingsService;

class AdminSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Initial Institute Profile & Identity Settings
        $profileSettings = [
            ['key' => 'state_name', 'value' => 'دولة ليبيا', 'label' => 'اسم الدولة', 'type' => 'string', 'setting_group' => 'institute_profile'],
            ['key' => 'supervising_body', 'value' => 'الهيئة العامة للأوقاف والشؤون الإسلامية', 'label' => 'الهيئة / الوزارة المشرفة', 'type' => 'string', 'setting_group' => 'institute_profile'],
            ['key' => 'supervising_department', 'value' => 'إدارة التعليم الأصيل', 'label' => 'الإدارة المشرفة', 'type' => 'string', 'setting_group' => 'institute_profile'],
            ['key' => 'institute_name', 'value' => 'المعهد التخصصي للعلوم الشرعية', 'label' => 'اسم المعهد المركزي', 'type' => 'string', 'setting_group' => 'institute_profile'],
            ['key' => 'branch_label', 'value' => 'الفرع الرئيسي', 'label' => 'مسمى الفرع الافتراضي', 'type' => 'string', 'setting_group' => 'institute_profile'],
            ['key' => 'phone', 'value' => '+218 21 444 5555', 'label' => 'هاتف المعهد', 'type' => 'string', 'setting_group' => 'institute_profile'],
            ['key' => 'email', 'value' => 'info@islamic-institute.edu.ly', 'label' => 'البريد الإلكتروني الرسمي', 'type' => 'string', 'setting_group' => 'institute_profile'],
            ['key' => 'address', 'value' => 'طرابلس - ليبيا', 'label' => 'العنوان الرسمي للمقر', 'type' => 'string', 'setting_group' => 'institute_profile'],
            ['key' => 'website', 'value' => 'https://islamic-institute.edu.ly', 'label' => 'الموقع الإلكتروني', 'type' => 'string', 'setting_group' => 'institute_profile'],
            ['key' => 'pobox', 'value' => 'ص.ب 80800', 'label' => 'صندوق البريد', 'type' => 'string', 'setting_group' => 'institute_profile'],
            ['key' => 'logo_url', 'value' => '/images/logo.png', 'label' => 'شعار المعهد المركزي', 'type' => 'image', 'setting_group' => 'branding'],
            ['key' => 'stamp_url', 'value' => '', 'label' => 'الختم الرسمي للمعهد', 'type' => 'image', 'setting_group' => 'branding'],
            ['key' => 'header_title', 'value' => 'المعهد التخصصي للعلوم الشرعية', 'label' => 'ترويسة المستندات والتقارير', 'type' => 'string', 'setting_group' => 'branding'],
            ['key' => 'footer_text', 'value' => 'المعهد التخصصي للعلوم الشرعية - إدارة التعليم الأصيل', 'label' => 'تذييل المطبوعات الرسمية', 'type' => 'string', 'setting_group' => 'branding'],
        ];

        foreach ($profileSettings as $item) {
            AdministrativeSetting::updateOrCreate(['key' => $item['key']], $item);
        }

        // 2. Organizational Units Hierarchy
        $units = [
            [
                'code' => 'GEN_DIR',
                'name' => 'الإدارة العامة للمعهد',
                'type' => 'general_admin',
                'parent_id' => null,
                'sort_order' => 1,
                'notes' => 'المكتب التنفيذي والإشراف العام',
            ],
            [
                'code' => 'ACAD_DEPT',
                'name' => 'إدارة الشؤون التعليمية والأكاديمية',
                'type' => 'department',
                'parent_id' => 1,
                'sort_order' => 2,
                'notes' => 'تختص بالخطط الدراسية والمناهج وتطوير الكادر التدريسي',
            ],
            [
                'code' => 'EXAM_SECTION',
                'name' => 'قسم شؤون الدراسة والامتحانات',
                'type' => 'section',
                'parent_id' => 2,
                'sort_order' => 3,
                'notes' => 'تنظيم الجداول والامتحانات ورصد النتائج',
            ],
            [
                'code' => 'STUDENT_AFFAIRS',
                'name' => 'قسم شؤون الطلبة والتسجيل',
                'type' => 'section',
                'parent_id' => 2,
                'sort_order' => 4,
                'notes' => 'شؤون القبول والتسجيل وملفات الطلاب والمعادلات',
            ],
            [
                'code' => 'BRANCH_MGMT',
                'name' => 'إدارة الفروع والمتابعة الميدانية',
                'type' => 'department',
                'parent_id' => 1,
                'sort_order' => 5,
                'notes' => 'التنسيق والإشراف على فروع المعهد في البلديات',
            ],
            [
                'code' => 'IT_UNIT',
                'name' => 'مكتب تقنية المعلومات والتوثيق',
                'type' => 'office',
                'parent_id' => 1,
                'sort_order' => 6,
                'notes' => 'إدارة المنظومة الإلكترونية وأمن قواعد البيانات',
            ],
            [
                'code' => 'QUALITY_COMMITTEE',
                'name' => 'لجنة الجودة والتقويم الأكاديمي',
                'type' => 'committee',
                'parent_id' => 1,
                'sort_order' => 7,
                'notes' => 'التفتيش الدوري والمراجعة الأكاديمية وضمان المعايير',
            ],
        ];

        $unitMap = [];
        foreach ($units as $u) {
            $created = OrganizationalUnit::updateOrCreate(
                ['code' => $u['code']],
                [
                    'name' => $u['name'],
                    'type' => $u['type'],
                    'sort_order' => $u['sort_order'],
                    'notes' => $u['notes'],
                    'is_active' => true,
                ]
            );
            $unitMap[$u['code']] = $created->id;
        }

        // Fix parent relationships
        OrganizationalUnit::where('code', 'ACAD_DEPT')->update(['parent_id' => $unitMap['GEN_DIR']]);
        OrganizationalUnit::where('code', 'EXAM_SECTION')->update(['parent_id' => $unitMap['ACAD_DEPT']]);
        OrganizationalUnit::where('code', 'STUDENT_AFFAIRS')->update(['parent_id' => $unitMap['ACAD_DEPT']]);
        OrganizationalUnit::where('code', 'BRANCH_MGMT')->update(['parent_id' => $unitMap['GEN_DIR']]);
        OrganizationalUnit::where('code', 'IT_UNIT')->update(['parent_id' => $unitMap['GEN_DIR']]);
        OrganizationalUnit::where('code', 'QUALITY_COMMITTEE')->update(['parent_id' => $unitMap['GEN_DIR']]);

        // 3. Official Job Positions (الصفات والمسميات الوظيفية)
        $positions = [
            ['code' => 'DIR_GEN', 'title' => 'المدير العام للمعهد', 'unit' => 'GEN_DIR', 'level' => 1, 'desc' => 'رأس الإدارة والاعتماد النهائي'],
            ['code' => 'DIR_BRANCH', 'title' => 'مدير فرع المعهد', 'unit' => 'BRANCH_MGMT', 'level' => 2, 'desc' => 'إدارة الفرع والاعتماد الميداني'],
            ['code' => 'HEAD_EXAMS', 'title' => 'رئيس قسم شؤون الدراسة والامتحانات', 'unit' => 'EXAM_SECTION', 'level' => 2, 'desc' => 'إدارة الشؤون الدراسية والامتحانية'],
            ['code' => 'HEAD_STUDENTS', 'title' => 'رئيس قسم شؤون الطلبة والتسجيل', 'unit' => 'STUDENT_AFFAIRS', 'level' => 2, 'desc' => 'إدارة التسجيل والملفات الطلابية'],
            ['code' => 'HEAD_IT', 'title' => 'مدير مكتب تقنية المعلومات والتوثيق', 'unit' => 'IT_UNIT', 'level' => 2, 'desc' => 'المشرف التقني على المنظومة'],
            ['code' => 'REGISTRAR', 'title' => 'مسجل شؤون الطلاب بالفرع', 'unit' => 'STUDENT_AFFAIRS', 'level' => 3, 'desc' => 'إعداد الكشوفات والشهادات'],
            ['code' => 'ATTENDANCE_OFFICER', 'title' => 'مشرف الحضور والانضباط المدرسي', 'unit' => 'STUDENT_AFFAIRS', 'level' => 3, 'desc' => 'تسجيل وتدقيق الحضور اليومي'],
            ['code' => 'QUALITY_INSPECTOR', 'title' => 'عضو لجنة الجودة والتدقيق', 'unit' => 'QUALITY_COMMITTEE', 'level' => 2, 'desc' => 'مراقبة الجودة والتفتيش'],
        ];

        $posMap = [];
        foreach ($positions as $p) {
            $pos = JobPosition::updateOrCreate(
                ['code' => $p['code']],
                [
                    'title' => $p['title'],
                    'organizational_unit_id' => $unitMap[$p['unit']] ?? null,
                    'level_order' => $p['level'],
                    'description' => $p['desc'],
                    'is_active' => true,
                ]
            );
            $posMap[$p['code']] = $pos->id;
        }

        // 4. Employee Placements (تسكين الموظفين الحاليين)
        $users = User::all();
        $adminUser = $users->firstWhere('email', 'admin@institute.edu') ?: $users->first();
        $firstBranch = Branch::first();

        if ($adminUser) {
            EmployeePlacement::updateOrCreate(
                [
                    'user_id' => $adminUser->id,
                    'job_position_id' => $posMap['DIR_GEN'],
                ],
                [
                    'organizational_unit_id' => $unitMap['GEN_DIR'],
                    'branch_id' => $firstBranch ? $firstBranch->id : null,
                    'start_date' => '2026-09-01',
                    'is_current' => true,
                    'status' => 'active',
                    'decision_number' => 'قرار إداري رقم (1) لسنة 2026',
                    'notes' => 'تسكين وظيفي معتمد',
                ]
            );
        }

        // 5. Official Document Signatories Configuration (ربط توقيعات ومسميات المستندات)
        $signatoriesConfig = [
            // Attendance Daily / Periodical Sheets
            ['doc' => 'attendance_sheet', 'slot' => 'prepared_by', 'label' => 'إعداد / مشرف الحضور والغياب', 'pos' => 'ATTENDANCE_OFFICER', 'override' => 'مشرف الحضور والانضباط'],
            ['doc' => 'attendance_sheet', 'slot' => 'verified_by', 'label' => 'مراجعة وتدقيق / رئيس قسم الدراسة والامتحانات', 'pos' => 'HEAD_EXAMS', 'override' => 'رئيس قسم شؤون الدراسة والامتحانات'],
            ['doc' => 'attendance_sheet', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير الفرع', 'pos' => 'DIR_BRANCH', 'override' => 'مدير فرع المعهد'],

            // Attendance Warning Notice
            ['doc' => 'warning_notice', 'slot' => 'prepared_by', 'label' => 'مسجل شؤون الطلاب', 'pos' => 'REGISTRAR', 'override' => 'مسجل شؤون الطلاب'],
            ['doc' => 'warning_notice', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير الفرع', 'pos' => 'DIR_BRANCH', 'override' => 'مدير فرع المعهد'],

            // Enrollment Certificate (شهادة قيد وتعريف)
            ['doc' => 'enrollment_cert', 'slot' => 'prepared_by', 'label' => 'رئيس قسم شؤون الطلبة والتسجيل', 'pos' => 'HEAD_STUDENTS', 'override' => 'رئيس قسم شؤون الطلبة والتسجيل'],
            ['doc' => 'enrollment_cert', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير عام المعهد', 'pos' => 'DIR_GEN', 'override' => 'مدير عام المعهد التخصصي للعلوم الشرعية'],

            // Good Conduct Certificate (شهادة حسن سيرة وسلوك)
            ['doc' => 'conduct_cert', 'slot' => 'prepared_by', 'label' => 'مسجل شؤون الطلاب', 'pos' => 'REGISTRAR', 'override' => 'مسجل شؤون الطلاب'],
            ['doc' => 'conduct_cert', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير عام المعهد', 'pos' => 'DIR_GEN', 'override' => 'مدير عام المعهد التخصصي للعلوم الشرعية'],

            // Secret Confidential Report (التقرير السري للفرع)
            ['doc' => 'secret_report', 'slot' => 'prepared_by', 'label' => 'مدير مكتب تقنية المعلومات والتوثيق', 'pos' => 'HEAD_IT', 'override' => 'مدير مكتب تقنية المعلومات والتوثيق'],
            ['doc' => 'secret_report', 'slot' => 'approved_by', 'label' => 'يعتمد / المدير العام للمعهد', 'pos' => 'DIR_GEN', 'override' => 'المدير العام للمعهد'],

            // Academic Transcript (كشف الدرجات الأكاديمي)
            ['doc' => 'transcript', 'slot' => 'prepared_by', 'label' => 'إعداد / مسجل الشؤون الأكاديمية', 'pos' => 'REGISTRAR', 'override' => 'مسجل الشؤون الأكاديمية'],
            ['doc' => 'transcript', 'slot' => 'verified_by', 'label' => 'تدقيق / رئيس قسم الدراسة والامتحانات', 'pos' => 'HEAD_EXAMS', 'override' => 'رئيس قسم شؤون الدراسة والامتحانات'],
            ['doc' => 'transcript', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير عام المعهد', 'pos' => 'DIR_GEN', 'override' => 'مدير عام المعهد التخصصي للعلوم الشرعية'],

            // Official Student Registry (سجل الطلاب العام والملفات المعتمدة)
            ['doc' => 'student_registry', 'slot' => 'prepared_by', 'label' => 'إعداد / مستخرج الكشف', 'pos' => 'REGISTRAR', 'override' => 'مسؤول شؤون الطلاب والامتحانات'],
            ['doc' => 'student_registry', 'slot' => 'verified_by', 'label' => 'تدقيق ومراجعة / رئيس قسم شؤون الطلبة والتسجيل', 'pos' => 'HEAD_STUDENTS', 'override' => 'رئيس قسم التسجيل وشؤون الطلاب'],
            ['doc' => 'student_registry', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير عام المعهد', 'pos' => 'DIR_GEN', 'override' => 'مدير عام المعهد التخصصي للعلوم الشرعية'],
        ];

        foreach ($signatoriesConfig as $sig) {
            OfficialDocumentSignatory::updateOrCreate(
                [
                    'document_code' => $sig['doc'],
                    'slot_key'      => $sig['slot'],
                ],
                [
                    'slot_label'            => $sig['label'],
                    'job_position_id'       => $posMap[$sig['pos']] ?? null,
                    'user_id'               => $adminUser ? $adminUser->id : null,
                    'custom_title_override' => $sig['override'],
                    'is_active'             => true,
                ]
            );
        }

        AdminSettingsService::clearCache();
    }
}
