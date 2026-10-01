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
        $admin = User::factory()->create();
        $response = $this->actingAs($admin)->get('/api/v1/students/sample-template');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
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
}
