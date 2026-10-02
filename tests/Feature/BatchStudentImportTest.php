<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Branch;
use App\Models\Department;
use App\Models\StudyYear;
use App\Models\AcademicYear;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class BatchStudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_download_sample_csv_template()
    {
        $response = $this->get('/api/v1/students/sample-template');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $responseCsv = $this->get('/api/v1/students/sample-template-csv');
        $responseCsv->assertStatus(200);
        $responseCsv->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_guest_can_download_sample_xlsx_template()
    {
        $response = $this->get('/api/v1/students/sample-template-xlsx');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_can_import_students_via_json_batch()
    {
        $branch = Branch::first() ?? Branch::create(['name' => 'طرابلس', 'code' => 'B01', 'city' => 'طرابلس', 'is_active' => true]);
        $dept = Department::first() ?? Department::create(['name' => 'الشريعة', 'code' => 'SH']);
        $studyYear = StudyYear::first() ?? StudyYear::create(['name' => 'السنة الأولى', 'year_number' => 1]);
        $academicYear = AcademicYear::where('is_current', true)->first() ?? AcademicYear::create(['name' => '2026/2027', 'code' => '2026', 'is_current' => true]);

        $adminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير عام', 'scope_type' => 'GLOBAL_SCOPE']);
        $admin = User::factory()->create(['role_id' => $adminRole->id, 'branch_id' => null]);
        $this->actingAs($admin);

        $payload = [
            'default_branch_id' => $branch->id,
            'default_department_id' => $dept->id,
            'default_study_year_id' => $studyYear->id,
            'students' => [
                [
                    'national_id' => '120050011111',
                    'first_name' => 'عمر',
                    'father_name' => 'خالد',
                    'grandfather_name' => 'إبراهيم',
                    'family_name' => 'الزواري',
                    'mother_name' => 'حنان محمد',
                    'gender' => 'ذكر',
                    'birth_date' => '2005-06-15',
                    'birth_place' => 'طرابلس',
                    'phone' => '091-1112233',
                    'guardian_phone' => '092-2223344',
                    'study_type' => 'نظامي',
                ],
                [
                    'national_id' => '220060022222',
                    'first_name' => 'سارة',
                    'father_name' => 'طارق',
                    'grandfather_name' => 'سالم',
                    'family_name' => 'القرقني',
                    'mother_name' => 'فاطمة عمر',
                    'gender' => 'أنثى',
                    'birth_date' => '2006-09-20',
                    'birth_place' => 'مصراتة',
                    'phone' => '091-3334455',
                    'guardian_phone' => '092-4445566',
                    'study_type' => 'انتساب',
                ],
                // Underage student (<15) should be reported in errors
                [
                    'national_id' => '120200033333',
                    'first_name' => 'طفل',
                    'father_name' => 'صغير',
                    'grandfather_name' => 'علي',
                    'family_name' => 'الصغير',
                    'mother_name' => 'مريم',
                    'gender' => 'ذكر',
                    'birth_date' => '2020-01-01',
                    'birth_place' => 'طرابلس',
                    'phone' => '091-0000000',
                    'guardian_phone' => '091-0000000',
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/students/import-batch', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('imported_count', 2);
        $response->assertJsonPath('failed_count', 1);

        $this->assertDatabaseHas('students', [
            'national_id' => '120050011111',
            'academic_status' => 'ENROLLED_ACTIVE',
        ]);
        $this->assertDatabaseHas('students', [
            'national_id' => '220060022222',
            'academic_status' => 'ENROLLED_ACTIVE',
        ]);
    }

    public function test_can_download_sample_xlsx_template()
    {
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/api/v1/students/sample-template-xlsx');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_can_import_students_via_xlsx_file_and_auto_create_branches()
    {
        $adminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير عام', 'scope_type' => 'GLOBAL_SCOPE']);
        $admin = User::factory()->create(['role_id' => $adminRole->id, 'branch_id' => null]);
        $this->actingAs($admin);

        $headers = [
            'الرقم الوطني', 'الاسم الأول', 'اسم الأب', 'اسم الجد', 'اللقب (اسم العائلة)',
            'اسم الأم', 'الجنس', 'تاريخ الميلاد', 'مكان الميلاد', 'الجنسية', 'الديانة',
            'رقم الهاتف', 'هاتف ولي الأمر', 'اسم ولي الأمر', 'صلة القرابة', 'رقم الطوارئ',
            'العنوان ومحل الإقامة', 'فصيلة الدم', 'الفرع التعليمي', 'القسم العلمي',
            'السنة الدراسية', 'نظام القيد', 'رقم القيد الوزاري', 'ملاحظات'
        ];

        $studentRow1 = [
            '120050077777', 'عبد المهيمن', 'عادل', 'سالم', 'الزليتني',
            'عائشة مصطفى', 'ذكر', '2005-03-10', 'زليتن', 'ليبي', 'مسلم',
            '091-7778899', '092-7778899', 'عادل سالم الزليتني', 'أب', '091-7778899',
            'زليتن - المنارة', 'O+', 'فرع زليتن الجديد', 'قسم الشريعة والقانون',
            'السنة الأولى', 'نظامي', 'MIN-ZLI-001', 'مستوفي مسوغات القبول'
        ];

        $studentRow2 = [
            '220060088888', 'مريم', 'جمعة', 'رمضان', 'الغرياني',
            'سليمة منصور', 'أنثى', '2006-07-25', 'غريان', 'ليبي', 'مسلم',
            '091-8889900', '092-8889900', 'جمعة رمضان الغرياني', 'أب', '092-8889900',
            'غريان - القواسم', 'A+', 'فرع غريان الجبل', 'قسم أصول الدين',
            'السنة الأولى', 'انتساب', 'MIN-GHR-002', 'طالبة انتساب'
        ];

        $xlsxData = \Shuchkin\SimpleXLSXGen::fromArray([$headers, $studentRow1, $studentRow2]);
        $tempPath = tempnam(sys_get_temp_dir(), 'test_student_xlsx_') . '.xlsx';
        file_put_contents($tempPath, (string)$xlsxData);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'students.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->postJson('/api/v1/students/import-batch', [
            'file' => $uploadedFile,
        ]);

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('imported_count', 2);
        $response->assertJsonPath('failed_count', 0);

        // Verify that students were created with official academic numbers
        $student1 = Student::where('national_id', '120050077777')->first();
        $this->assertNotNull($student1);
        $this->assertNotEmpty($student1->academic_number);
        $this->assertEquals('MALE', $student1->gender);
        $this->assertEquals('1', substr($student1->academic_number, 0, 1)); // Starts with 1 for male

        $student2 = Student::where('national_id', '220060088888')->first();
        $this->assertNotNull($student2);
        $this->assertNotEmpty($student2->academic_number);
        $this->assertEquals('FEMALE', $student2->gender);
        $this->assertEquals('2', substr($student2->academic_number, 0, 1)); // Starts with 2 for female

        // Verify that the new branches were automatically created in branches directory!
        $this->assertDatabaseHas('branches', [
            'name' => 'فرع زليتن الجديد',
            'city' => 'زليتن',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('branches', [
            'name' => 'فرع غريان الجبل',
            'city' => 'غريان',
            'is_active' => true,
        ]);
    }

    public function test_can_download_branches_sample_and_import_branches_excel()
    {
        $adminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير عام', 'scope_type' => 'GLOBAL_SCOPE']);
        $admin = User::factory()->create(['role_id' => $adminRole->id, 'branch_id' => null]);
        $this->actingAs($admin);

        // Download branch template
        $templateRes = $this->get('/api/v1/branches/sample-template-xlsx');
        $templateRes->assertStatus(200);
        $templateRes->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Import branches via Excel
        $headers = [
            'رمز الفرع', 'اسم الفرع', 'الاسم المختصر', 'المدينة', 'المنطقة',
            'نوع الفرع', 'نوع المقر', 'حالة الفرع', 'اسم المدير', 'هاتف المدير',
            'هاتف الفرع', 'البريد الإلكتروني', 'خط العرض', 'خط الطول', 'الملاحظات'
        ];
        $branchRow = [
            'DER-09', 'فرع درنة التاريخي', 'درنة', 'درنة', 'المنطقة الشرقية',
            'فرعي', 'ملك للدولة', 'ACTIVE', 'أ. مصطفى الشاعري', '091-9988112',
            '081-4455667', 'derna@iiis.edu.ly', 32.7667, 22.6367, 'فرع الجبل الأخضر'
        ];

        $xlsxData = \Shuchkin\SimpleXLSXGen::fromArray([$headers, $branchRow]);
        $tempPath = tempnam(sys_get_temp_dir(), 'test_branch_xlsx_') . '.xlsx';
        file_put_contents($tempPath, (string)$xlsxData);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'branches.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $importRes = $this->postJson('/api/v1/branches/import-excel', [
            'file' => $uploadedFile,
        ]);

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }

        $importRes->assertStatus(200);
        $importRes->assertJsonPath('status', 'success');
        $importRes->assertJsonPath('imported_count', 1);

        $this->assertDatabaseHas('branches', [
            'code' => 'DER-09',
            'name' => 'فرع درنة التاريخي',
            'city' => 'درنة',
            'is_active' => true,
        ]);
    }
}
