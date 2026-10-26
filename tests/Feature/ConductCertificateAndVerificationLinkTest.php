<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\DocumentVerification;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentBehavior;
use App\Models\StudyYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The good-conduct certificate is never issued over a severe violation, and the verification link printed
 * on the enrollment and conduct certificates opens the public verification page.
 */
class ConductCertificateAndVerificationLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']);
        $this->admin = User::factory()->create(['role_id' => $role->id]);

        $branch = Branch::create(['name' => 'فرع السلوك', 'code' => 'CND-01', 'city' => 'طرابلس', 'branch_status' => 'ACTIVE']);
        $academicYear = AcademicYear::create([
            'name' => '2026/2027', 'code' => 'AY-2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true,
        ]);
        $studyYear = StudyYear::create(['name' => 'السنة الأولى', 'level_order' => 1]);
        $department = Department::create(['name' => 'الشريعة', 'code' => 'SHR-CND']);

        $this->student = Student::create([
            'first_name' => 'محمد', 'father_name' => 'أحمد', 'grandfather_name' => 'سالم', 'family_name' => 'الليبي',
            'mother_name' => 'فاطمة عمر', 'national_id' => '199912345678', 'birth_date' => '2005-01-01', 'birth_place' => 'طرابلس',
            'gender' => 'MALE', 'nationality' => 'ليبي', 'nationality_category' => 'LIBYAN',
            'phone' => '0912345678', 'guardian_phone' => '0923456789', 'branch_id' => $branch->id,
            'current_study_year_id' => $studyYear->id, 'enrolled_academic_year_id' => $academicYear->id,
            'academic_year_id' => $academicYear->id, 'department_id' => $department->id,
            'academic_status' => 'ENROLLED_ACTIVE', 'study_type' => 'REGULAR', 'is_archived' => false,
            'academic_number' => 'M26-0003',
        ]);
    }

    private function behavior(string $level): void
    {
        StudentBehavior::create([
            'student_id' => $this->student->id,
            'violation_type' => 'مخالفة اختبارية',
            'warning_level' => $level,
            'violation_date' => '2026-10-01',
        ]);
    }

    public function test_a_clean_record_gets_the_certificate(): void
    {
        $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/certificates/conduct")
            ->assertOk()
            ->assertJsonPath('certificate.clean_record', true);
    }

    public function test_a_minor_warning_does_not_block_the_certificate(): void
    {
        $this->behavior('LEVEL_1');
        $this->behavior('LEVEL_2');

        $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/certificates/conduct")
            ->assertOk();
    }

    public function test_a_final_warning_blocks_the_certificate_and_registers_no_document(): void
    {
        $this->behavior('LEVEL_3');

        $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/certificates/conduct")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(0, DocumentVerification::where('student_id', $this->student->id)->count());
    }

    public function test_the_enrollment_certificate_is_not_affected_by_the_behavior_record(): void
    {
        $this->behavior('LEVEL_3');

        $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/certificates/enrollment")
            ->assertOk();
    }

    public function test_the_public_verification_page_carries_the_institute_name_from_the_central_settings(): void
    {
        \App\Services\AdminSettingsService::setSetting('institute_name', 'المعهد التخصصي للدراسات الإسلامية', 'institute_profile');

        $html = $this->get('/verify/00000000-0000-0000-0000-000000000000')->assertOk()->getContent();

        $this->assertStringContainsString('المعهد التخصصي للدراسات الإسلامية', $html);
        $this->assertStringNotContainsString('للعلوم والمهن', $html);
        $this->assertStringNotContainsString('للعلوم الشرعية', $html);
    }

    public static function certificateKinds(): array
    {
        return [['enrollment'], ['conduct']];
    }

    #[DataProvider('certificateKinds')]
    public function test_the_printed_verification_link_opens_the_public_page(string $kind): void
    {
        $link = $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/certificates/{$kind}")
            ->assertOk()
            ->json('certificate.qr_verification_code');

        $uuid = DocumentVerification::where('student_id', $this->student->id)->latest('id')->value('document_uuid');
        $this->assertSame(url("/verify/{$uuid}"), $link);

        $this->get(parse_url($link, PHP_URL_PATH))->assertOk();
        $this->getJson(parse_url($link, PHP_URL_PATH))->assertOk()->assertJsonPath('success', true);
    }
}
