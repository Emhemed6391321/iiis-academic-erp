<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\StudyYear;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Services\StudentDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The required documents follow the student's nationality category, and final HQ approval waits for them.
 */
class MandatoryDocumentsByStatusTest extends TestCase
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

        $superRole = Role::create(['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']);
        $this->admin = User::factory()->create(['role_id' => $superRole->id]);
        $this->branch = Branch::create(['name' => 'فرع المستندات', 'code' => 'DOC-01', 'city' => 'طرابلس', 'branch_status' => 'ACTIVE']);
        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027', 'code' => 'AY-2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true,
        ]);
        $this->studyYear = StudyYear::create(['name' => 'السنة الأولى', 'level_order' => 1]);
        $this->department = Department::create(['name' => 'الشريعة', 'code' => 'SHR-DOC']);
    }

    private function createStudent(string $category): Student
    {
        return Student::create([
            'first_name' => 'عمر', 'father_name' => 'خالد', 'grandfather_name' => 'يوسف', 'family_name' => 'المصراتي',
            'mother_name' => 'مريم علي', 'national_id' => $category === 'LIBYAN' ? '199912345679' : null,
            'passport_number' => $category === 'OTHER' ? 'PA123456' : null,
            'birth_date' => '2006-02-02', 'birth_place' => 'مصراتة', 'gender' => 'MALE',
            'nationality' => $category === 'OTHER' ? 'مصري' : 'ليبي', 'nationality_category' => $category,
            'phone' => '0917654321', 'guardian_phone' => '0927654321', 'branch_id' => $this->branch->id,
            'current_study_year_id' => $this->studyYear->id, 'enrolled_academic_year_id' => $this->academicYear->id,
            'academic_year_id' => $this->academicYear->id, 'department_id' => $this->department->id,
            'academic_status' => 'PENDING_HQ', 'study_type' => 'REGULAR', 'is_archived' => false,
        ]);
    }

    private function upload(Student $student, string $type): void
    {
        StudentDocument::create([
            'student_id' => $student->id,
            'document_type' => $type,
            'original_name' => strtolower($type) . '.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'is_required' => true,
            'file_path' => 'secure_vault/test/' . $type . '.enc',
            'file_hash' => hash('sha256', $type),
            'uploaded_by' => $this->admin->id,
        ]);
    }

    public function test_libyan_student_needs_identity_birth_and_basic_education_plus_health_documents(): void
    {
        $student = $this->createStudent('LIBYAN');
        $service = app(StudentDocumentService::class);

        $this->assertEqualsCanonicalizing(
            ['NATIONAL_ID_CARD', 'BIRTH_CERT', 'BASIC_EDUCATION_CERT', 'HEALTH_CERT'],
            $service->missingRequiredTypes($student)
        );
    }

    public function test_non_libyan_student_needs_foreign_birth_certificate_passport_residence_and_health_documents(): void
    {
        $student = $this->createStudent('OTHER');
        $service = app(StudentDocumentService::class);

        $this->assertEqualsCanonicalizing(
            ['FOREIGN_BIRTH_CERT', 'PASSPORT_COPY', 'RESIDENCE_PERMIT', 'HEALTH_CERT'],
            $service->missingRequiredTypes($student)
        );
    }

    public function test_non_libyan_final_approval_waits_for_the_foreign_birth_certificate(): void
    {
        $student = $this->createStudent('OTHER');
        $required = ['FOREIGN_BIRTH_CERT', 'PASSPORT_COPY', 'RESIDENCE_PERMIT', 'HEALTH_CERT'];

        $refused = $this->actingAs($this->admin)->postJson("/api/v1/students/{$student->id}/approve-hq");
        $refused->assertStatus(422);
        $this->assertEqualsCanonicalizing($required, $refused->json('missing_documents'));

        foreach ($required as $type) {
            $this->upload($student, $type);
        }

        $this->actingAs($this->admin)->postJson("/api/v1/students/{$student->id}/approve-hq")->assertOk();
        $this->assertNotNull($student->fresh()->academic_number);
    }

    public function test_foreign_birth_certificate_is_recognised_by_its_arabic_label(): void
    {
        $this->assertSame(['FOREIGN_BIRTH_CERT', null], StudentDocumentService::resolveType('شهادة ميلاد لغير الليبيين'));
        $this->assertSame(['FOREIGN_BIRTH_CERT', null], StudentDocumentService::resolveType('FOREIGN_BIRTH_CERT'));
    }

    public function test_final_hq_approval_is_refused_until_mandatory_documents_are_complete(): void
    {
        $student = $this->createStudent('LIBYAN');

        $refused = $this->actingAs($this->admin)->postJson("/api/v1/students/{$student->id}/approve-hq");
        $refused->assertStatus(422);
        $this->assertEqualsCanonicalizing(
            ['NATIONAL_ID_CARD', 'BIRTH_CERT', 'BASIC_EDUCATION_CERT', 'HEALTH_CERT'],
            $refused->json('missing_documents')
        );
        $this->assertNull($student->fresh()->academic_number);

        foreach (['NATIONAL_ID_CARD', 'BIRTH_CERT', 'BASIC_EDUCATION_CERT', 'HEALTH_CERT'] as $type) {
            $this->upload($student, $type);
        }

        $this->actingAs($this->admin)->postJson("/api/v1/students/{$student->id}/approve-hq")
            ->assertOk();
        $this->assertNotNull($student->fresh()->academic_number);
    }
}
