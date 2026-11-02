<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudyYear;
use App\Models\User;
use App\Services\StudentDocumentService;
use App\Services\StudentStateMachineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * لا يُعتمد أي طالب إلا بعد اكتمال بياناته الإلزامية ومستنداته الموجودة فعلياً، في كل مسارات الاعتماد.
 */
class StudentApprovalCompletenessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branch;
    private AcademicYear $academicYear;
    private StudyYear $studyYear;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $role = Role::create(['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']);
        $this->admin = User::factory()->create(['role_id' => $role->id]);
        $this->branch = Branch::create(['name' => 'فرع الاعتماد', 'code' => 'APR-01', 'city' => 'طرابلس', 'branch_status' => 'ACTIVE']);
        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027', 'code' => 'AY-2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true,
        ]);
        $this->studyYear = StudyYear::create(['name' => 'السنة الأولى', 'level_order' => 1]);
        $this->department = Department::create(['name' => 'الشريعة', 'code' => 'SHR-APR']);
    }

    private function student(string $category, array $override = []): Student
    {
        return Student::create(array_merge([
            'first_name' => 'عمر', 'father_name' => 'خالد', 'grandfather_name' => 'يوسف', 'family_name' => 'المصراتي',
            'mother_name' => 'مريم علي', 'national_id' => $category === 'LIBYAN' ? '199912345679' : null,
            'passport_number' => $category === 'OTHER' ? 'PA123456' : null,
            'birth_date' => '2006-02-02', 'birth_place' => 'مصراتة', 'gender' => 'MALE',
            'nationality' => $category === 'OTHER' ? 'مصري' : 'ليبي', 'nationality_category' => $category,
            'phone' => '0917654321', 'guardian_phone' => '0927654321', 'branch_id' => $this->branch->id,
            'current_study_year_id' => $this->studyYear->id, 'enrolled_academic_year_id' => $this->academicYear->id,
            'academic_year_id' => $this->academicYear->id, 'department_id' => $this->department->id,
            'academic_status' => 'PENDING_HQ', 'study_type' => 'REGULAR', 'is_archived' => false,
        ], $override));
    }

    private function attach(Student $student, string $type, bool $withFile = true): void
    {
        $path = 'secure_vault/test/' . $student->id . '/' . $type . '.enc';
        if ($withFile) {
            Storage::disk('local')->put($path, 'x');
        }
        StudentDocument::create([
            'student_id' => $student->id, 'document_type' => $type, 'original_name' => $type . '.pdf',
            'file_size' => 1024, 'mime_type' => 'application/pdf', 'is_required' => true,
            'file_path' => $path, 'file_hash' => hash('sha256', $type), 'uploaded_by' => $this->admin->id,
        ]);
    }

    private function attachAll(Student $student, array $except = []): void
    {
        foreach (app(StudentDocumentService::class)->requiredTypesFor($student) as $type) {
            if (!in_array($type, $except, true)) {
                $this->attach($student, $type);
            }
        }
    }

    private function approve(Student $student)
    {
        return $this->actingAs($this->admin)->postJson("/api/v1/students/{$student->id}/approve-hq");
    }

    public function test_libyan_without_basic_education_certificate_is_refused(): void
    {
        $s = $this->student('LIBYAN');
        $this->attachAll($s, ['BASIC_EDUCATION_CERT']);
        $this->approve($s)->assertStatus(422)->assertJsonPath('redirect_student_id', $s->id);
        $this->assertSame('PENDING_HQ', $s->fresh()->academic_status);
    }

    public function test_libyan_without_birth_certificate_is_refused(): void
    {
        $s = $this->student('LIBYAN');
        $this->attachAll($s, ['BIRTH_CERT']);
        $this->approve($s)->assertStatus(422);
        $this->assertNull($s->fresh()->approved_at);
    }

    public function test_libyan_without_both_documents_is_refused(): void
    {
        $s = $this->student('LIBYAN');
        $this->attachAll($s, ['BASIC_EDUCATION_CERT', 'BIRTH_CERT']);
        $this->approve($s)->assertStatus(422);
    }

    public function test_complete_libyan_is_approved(): void
    {
        $s = $this->student('LIBYAN');
        $this->attachAll($s);
        $this->approve($s)->assertOk();
        $this->assertSame('ENROLLED_ACTIVE', $s->fresh()->academic_status);
    }

    public function test_non_libyan_without_passport_is_refused(): void
    {
        $s = $this->student('OTHER');
        $this->attachAll($s, ['PASSPORT_COPY']);
        $this->approve($s)->assertStatus(422);
    }

    public function test_non_libyan_without_residence_is_refused(): void
    {
        $s = $this->student('OTHER');
        $this->attachAll($s, ['RESIDENCE_PERMIT']);
        $this->approve($s)->assertStatus(422);
    }

    public function test_non_libyan_with_partial_documents_is_refused(): void
    {
        $s = $this->student('OTHER');
        $this->attach($s, 'BASIC_EDUCATION_CERT');
        $this->approve($s)->assertStatus(422);
    }

    public function test_complete_non_libyan_is_approved(): void
    {
        $s = $this->student('OTHER');
        $this->attachAll($s);
        $this->approve($s)->assertOk();
        $this->assertSame('ENROLLED_ACTIVE', $s->fresh()->academic_status);
    }

    public function test_all_documents_but_missing_mandatory_field_is_refused(): void
    {
        $s = $this->student('LIBYAN', ['mother_name' => '']);
        $this->attachAll($s);
        $res = $this->approve($s)->assertStatus(422);
        $this->assertContains('اسم الأم', $res->json('blockers.missing_fields'));
        $this->assertSame('PENDING_HQ', $s->fresh()->academic_status);
    }

    public function test_document_row_without_a_file_in_storage_is_refused(): void
    {
        $s = $this->student('LIBYAN');
        $this->attachAll($s, ['BIRTH_CERT']);
        $this->attach($s, 'BIRTH_CERT', withFile: false);
        $res = $this->approve($s)->assertStatus(422);
        $this->assertNotEmpty($res->json('blockers.unreadable_documents'));
    }

    public function test_direct_requests_to_the_other_approval_endpoints_are_refused(): void
    {
        $s = $this->student('OTHER', ['academic_status' => 'ENROLLED_ACTIVE']);
        $this->attach($s, 'BASIC_EDUCATION_CERT');

        $this->actingAs($this->admin)->postJson("/api/v1/students/{$s->id}/file/approve")->assertStatus(422);
        $this->actingAs($this->admin)->postJson("/api/v1/students/{$s->id}/file/verify")->assertStatus(422);
        $this->assertNull($s->fresh()->approved_at);
        $this->assertNull($s->fresh()->data_verified_at);
    }

    public function test_state_machine_refuses_enrolment_of_an_incomplete_pending_file(): void
    {
        $s = $this->student('LIBYAN');
        $this->actingAs($this->admin);
        $check = app(StudentStateMachineService::class)->canTransition($s, 'ENROLLED_ACTIVE', $this->admin);
        $this->assertFalse($check['allowed']);
    }

    public function test_renewal_of_an_already_approved_suspended_student_is_not_blocked(): void
    {
        $s = $this->student('LIBYAN', ['academic_status' => 'SUSPENDED']);
        $check = app(StudentStateMachineService::class)->canTransition($s, 'ENROLLED_ACTIVE', $this->admin);
        $this->assertTrue($check['allowed']);
    }
}
