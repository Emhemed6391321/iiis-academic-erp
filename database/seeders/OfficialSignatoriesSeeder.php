<?php

namespace Database\Seeders;

use App\Models\JobPosition;
use App\Models\OfficialDocumentSignatory;
use App\Services\AdminSettingsService;
use Illuminate\Database\Seeder;

/**
 * Creates the official signature slots that are missing from the directory (مسميات التوقيعات والاعتمادات).
 * Only missing slots are created: a slot that already exists, including any edit made on the server, is never changed.
 * Positions are matched by their title, because the codes on the live server are not the seeder's codes.
 */
class OfficialSignatoriesSeeder extends Seeder
{
    public function run(): void
    {
        $positionByTitle = [
            'HEAD_EXAMS' => 'رئيس قسم الدراسة والامتحانات (الإدارة المركزية)',
            'HEAD_STUDENTS' => 'رئيس قسم شؤون الطلبة (الإدارة المركزية)',
            'REGISTRAR' => 'رئيس قسم شؤون الطلبة (الإدارة المركزية)',
            'DIR_BRANCH' => 'مدير المعهد (الفرع)',
            'DIR_GEN' => 'المدير العام',
            'HEAD_IT' => 'مدير مكتب المعلومات والتوثيق',
        ];

        $slots = [
            ['doc' => 'attendance_sheet', 'slot' => 'prepared_by', 'label' => 'إعداد / مشرف الحضور والغياب', 'pos' => null, 'override' => 'مشرف الحضور والانضباط'],
            ['doc' => 'attendance_sheet', 'slot' => 'verified_by', 'label' => 'مراجعة وتدقيق / رئيس قسم الدراسة والامتحانات', 'pos' => 'HEAD_EXAMS', 'override' => 'رئيس قسم شؤون الدراسة والامتحانات'],
            ['doc' => 'attendance_sheet', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير الفرع', 'pos' => 'DIR_BRANCH', 'override' => 'مدير فرع المعهد'],

            ['doc' => 'warning_notice', 'slot' => 'prepared_by', 'label' => 'مسجل شؤون الطلاب', 'pos' => 'REGISTRAR', 'override' => 'مسجل شؤون الطلاب'],
            ['doc' => 'warning_notice', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير الفرع', 'pos' => 'DIR_BRANCH', 'override' => 'مدير فرع المعهد'],

            ['doc' => 'enrollment_cert', 'slot' => 'issued_by', 'label' => 'الموظف المختص بالتسجيل', 'pos' => null, 'override' => 'الموظف المختص بالتسجيل'],
            ['doc' => 'enrollment_cert', 'slot' => 'prepared_by', 'label' => 'رئيس قسم شؤون الطلبة والتسجيل', 'pos' => 'HEAD_STUDENTS', 'override' => 'رئيس قسم شؤون الطلبة والتسجيل'],
            ['doc' => 'enrollment_cert', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير عام المعهد', 'pos' => 'DIR_GEN', 'override' => 'مدير عام المعهد التخصصي للعلوم الشرعية'],

            ['doc' => 'conduct_cert', 'slot' => 'issued_by', 'label' => 'الموظف المستخرج للشهادة', 'pos' => null, 'override' => 'الموظف المستخرج للشهادة'],
            ['doc' => 'conduct_cert', 'slot' => 'prepared_by', 'label' => 'مسجل شؤون الطلاب', 'pos' => 'REGISTRAR', 'override' => 'مسجل شؤون الطلاب'],
            ['doc' => 'conduct_cert', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير عام المعهد', 'pos' => 'DIR_GEN', 'override' => 'مدير عام المعهد التخصصي للعلوم الشرعية'],

            ['doc' => 'secret_report', 'slot' => 'prepared_by', 'label' => 'مدير مكتب تقنية المعلومات والتوثيق', 'pos' => 'HEAD_IT', 'override' => 'مدير مكتب تقنية المعلومات والتوثيق'],
            ['doc' => 'secret_report', 'slot' => 'approved_by', 'label' => 'يعتمد / المدير العام للمعهد', 'pos' => 'DIR_GEN', 'override' => 'المدير العام للمعهد'],

            ['doc' => 'transcript', 'slot' => 'prepared_by', 'label' => 'إعداد / مسجل الشؤون الأكاديمية', 'pos' => 'REGISTRAR', 'override' => 'مسجل الشؤون الأكاديمية'],
            ['doc' => 'transcript', 'slot' => 'verified_by', 'label' => 'تدقيق / رئيس قسم الدراسة والامتحانات', 'pos' => 'HEAD_EXAMS', 'override' => 'رئيس قسم شؤون الدراسة والامتحانات'],
            ['doc' => 'transcript', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير عام المعهد', 'pos' => 'DIR_GEN', 'override' => 'مدير عام المعهد التخصصي للعلوم الشرعية'],

            ['doc' => 'student_registry', 'slot' => 'prepared_by', 'label' => 'إعداد / مستخرج الكشف', 'pos' => 'REGISTRAR', 'override' => 'مسؤول شؤون الطلاب والامتحانات'],
            ['doc' => 'student_registry', 'slot' => 'verified_by', 'label' => 'تدقيق ومراجعة / رئيس قسم شؤون الطلبة والتسجيل', 'pos' => 'HEAD_STUDENTS', 'override' => 'رئيس قسم التسجيل وشؤون الطلاب'],
            ['doc' => 'student_registry', 'slot' => 'approved_by', 'label' => 'يعتمد / مدير عام المعهد', 'pos' => 'DIR_GEN', 'override' => 'مدير عام المعهد التخصصي للعلوم الشرعية'],

            ['doc' => 'data_quality', 'slot' => 'prepared_by', 'label' => 'مسؤول فحص الجودة والإدخال', 'pos' => null, 'override' => 'مسؤول فحص الجودة والإدخال'],
            ['doc' => 'data_quality', 'slot' => 'verified_by', 'label' => 'رئيس قسم الشؤون التعليمية بالفرع', 'pos' => null, 'override' => 'رئيس قسم الشؤون التعليمية بالفرع'],
            ['doc' => 'data_quality', 'slot' => 'approved_by', 'label' => 'مدير فرع المعهد', 'pos' => 'DIR_BRANCH', 'override' => 'مدير فرع المعهد التخصصي'],
        ];

        $created = 0;
        foreach ($slots as $slot) {
            $positionId = $slot['pos']
                ? JobPosition::where('title', $positionByTitle[$slot['pos']])->value('id')
                : null;

            $row = OfficialDocumentSignatory::firstOrCreate(
                ['document_code' => $slot['doc'], 'slot_key' => $slot['slot']],
                [
                    'slot_label' => $slot['label'],
                    'job_position_id' => $positionId,
                    'user_id' => null,
                    'custom_title_override' => $slot['override'],
                    'is_active' => true,
                    'show_name' => true,
                    'show_title' => true,
                ]
            );

            $created += $row->wasRecentlyCreated ? 1 : 0;
        }

        AdminSettingsService::clearCache();

        $this->command?->info("✅ مسميات التوقيعات: أُضيف {$created} خانة ناقصة، وبقيت الخانات الموجودة كما هي.");
    }
}
