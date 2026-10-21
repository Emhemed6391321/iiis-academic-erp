<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TranscriptEngineController;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeePlacement;
use App\Models\JobPosition;
use App\Models\OfficialDocumentSignatory;
use App\Models\Role;
use App\Models\StudyYear;
use App\Models\Student;
use App\Models\User;
use App\Services\AdminSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Every official form that carries a signature reads its signatories from the central settings.
 * The seven forms: enrollment_cert, conduct_cert, secret_report, transcript, warning_notice,
 * attendance_sheet, student_registry.
 */
class OfficialSignatoriesCentralTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branch;
    private Student $student;
    private User $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $superRole = Role::create(['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']);
        $this->admin = User::factory()->create(['role_id' => $superRole->id]);

        $registrarRole = Role::create(['name' => 'branch_registrar', 'display_name' => 'مسجل شؤون الطلبة', 'scope_type' => 'BRANCH_SCOPE']);
        AdminSettingsService::syncRolePositions();
        $position = JobPosition::where('role_id', $registrarRole->id)->firstOrFail();

        $this->branch = Branch::create(['name' => 'فرع التوقيع', 'code' => 'SIG-01', 'city' => 'طرابلس', 'branch_status' => 'ACTIVE']);
        $this->registrar = User::factory()->create(['name' => 'علي المسجل', 'role_id' => $registrarRole->id, 'branch_id' => $this->branch->id]);
        EmployeePlacement::create([
            'user_id' => $this->registrar->id,
            'job_position_id' => $position->id,
            'branch_id' => $this->branch->id,
            'start_date' => '2026-09-01',
            'is_current' => true,
            'status' => 'active',
            'show_name_on_documents' => true,
        ]);

        // Central configuration: every signed document has a prepared_by slot (a named officer) and an approved_by slot (a title).
        $codes = ['enrollment_cert', 'conduct_cert', 'secret_report', 'transcript', 'warning_notice', 'attendance_sheet', 'student_registry'];
        foreach ($codes as $code) {
            OfficialDocumentSignatory::create([
                'document_code' => $code,
                'slot_key' => 'prepared_by',
                'slot_label' => 'إعداد الوثيقة',
                'user_id' => $this->registrar->id,
                'job_position_id' => $position->id,
                'custom_title_override' => 'مسجل شؤون الطلبة المعتمد',
                'is_active' => true,
            ]);
            OfficialDocumentSignatory::create([
                'document_code' => $code,
                'slot_key' => 'approved_by',
                'slot_label' => 'يعتمد الوثيقة',
                'custom_title_override' => 'مدير الفرع المعتمد',
                'is_active' => true,
            ]);
        }
        AdminSettingsService::clearCache();

        $academicYear = AcademicYear::create([
            'name' => '2026/2027', 'code' => 'AY-2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true,
        ]);
        $studyYear = StudyYear::create(['name' => 'السنة الأولى', 'level_order' => 1]);
        $department = Department::create(['name' => 'الشريعة', 'code' => 'SHR-SIG']);

        $this->student = Student::create([
            'first_name' => 'محمد', 'father_name' => 'أحمد', 'grandfather_name' => 'سالم', 'family_name' => 'الليبي',
            'mother_name' => 'فاطمة عمر', 'national_id' => '199912345678', 'birth_date' => '2005-01-01', 'birth_place' => 'طرابلس',
            'gender' => 'MALE', 'nationality' => 'ليبي', 'nationality_category' => 'LIBYAN',
            'phone' => '0912345678', 'guardian_phone' => '0923456789', 'branch_id' => $this->branch->id,
            'current_study_year_id' => $studyYear->id, 'enrolled_academic_year_id' => $academicYear->id,
            'academic_year_id' => $academicYear->id, 'department_id' => $department->id,
            'academic_status' => 'ENROLLED_ACTIVE', 'study_type' => 'REGULAR', 'is_archived' => false,
            'academic_number' => 'M26-0001',
        ]);
    }

    private function assertCentralSignatories(array $signatories, string $slot = 'prepared_by'): void
    {
        $this->assertSame('مسجل شؤون الطلبة المعتمد', $signatories[$slot]['title']);
        $this->assertSame($this->registrar->name, $signatories[$slot]['name']);
        $this->assertSame('مدير الفرع المعتمد', $signatories['approved_by']['title']);
    }

    public function test_enrollment_certificate_reads_signatories_from_central_settings(): void
    {
        $response = $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/certificates/enrollment");

        $response->assertOk();
        $this->assertCentralSignatories($response->json('certificate.signatories'));
    }

    public function test_good_conduct_certificate_reads_signatories_from_central_settings(): void
    {
        $response = $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/certificates/conduct");

        $response->assertOk();
        $this->assertCentralSignatories($response->json('certificate.signatories'));
    }

    public function test_certificate_issuer_comes_from_a_central_slot_not_the_logged_in_user(): void
    {
        OfficialDocumentSignatory::create([
            'document_code' => 'enrollment_cert',
            'slot_key' => 'issued_by',
            'slot_label' => 'الموظف المختص بالتسجيل',
            'user_id' => $this->registrar->id,
            'custom_title_override' => 'الموظف المختص بالتسجيل',
            'is_active' => true,
        ]);
        AdminSettingsService::clearCache();

        $response = $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/certificates/enrollment");

        $response->assertOk();
        $response->assertJsonMissingPath('certificate.issuer');
        $this->assertSame($this->registrar->name, $response->json('certificate.signatories.issued_by.name'));
        $this->assertSame('الموظف المختص بالتسجيل', $response->json('certificate.signatories.issued_by.title'));
    }

    public function test_confidential_report_reads_signatories_from_central_settings(): void
    {
        $response = $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/confidential-report");

        $response->assertOk();
        $this->assertCentralSignatories($response->json('report.signatories'));
    }

    public function test_transcript_reads_signatories_from_central_settings(): void
    {
        // The transcripts module is closed at the server (ModuleClosedMiddleware), so the controller is called directly.
        $response = app(TranscriptEngineController::class)->generate(Request::create('/transcript', 'GET'), $this->student);
        $payload = $response->getData(true);

        $this->assertCentralSignatories($payload['signatories']);
    }

    public function test_warning_notice_reads_officer_and_director_from_central_settings(): void
    {
        $response = $this->actingAs($this->admin)->postJson("/api/v1/attendance/issue-warning/{$this->student->id}", [
            'warning_level' => 'FIRST_WARNING',
            'unexcused_days' => 4,
        ]);

        $response->assertOk();
        $signatories = $response->json('notice.signatories');
        $this->assertSame('مسجل شؤون الطلبة المعتمد', $signatories['officer_role']);
        $this->assertSame($this->registrar->name, $signatories['officer_name']);
        $this->assertSame('مدير الفرع المعتمد', $signatories['director_title']);
    }

    public function test_daily_attendance_sheet_reads_signatories_from_central_settings(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/attendance/reports?' . http_build_query([
            'report_type' => 'DAILY_SHEET',
            'date' => '2026-10-17',
            'branch_id' => $this->branch->id,
        ]));

        $response->assertOk();
        $this->assertCentralSignatories($response->json('report.signatories'));
    }

    public function test_official_registry_reads_signatories_from_central_settings(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/students/registry/official');

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('truncated', false);
        $response->assertJsonPath('rows.0.academic_number', 'M26-0001');
        $this->assertCentralSignatories($response->json('signatories'));
    }

    public function test_hidden_name_keeps_the_title_on_the_official_registry(): void
    {
        EmployeePlacement::query()->update(['show_name_on_documents' => false]);
        AdminSettingsService::clearCache();

        $response = $this->actingAs($this->admin)->getJson('/api/v1/students/registry/official');

        $response->assertOk();
        $this->assertSame('', $response->json('signatories.prepared_by.name'));
        $this->assertSame('مسجل شؤون الطلبة المعتمد', $response->json('signatories.prepared_by.title'));
    }

    public function test_official_registry_requires_students_view_permission(): void
    {
        $plainRole = Role::create(['name' => 'branch_social_worker', 'display_name' => 'أخصائي اجتماعي', 'scope_type' => 'BRANCH_SCOPE']);
        $outsider = User::factory()->create(['role_id' => $plainRole->id, 'branch_id' => $this->branch->id]);

        $this->actingAs($outsider)->getJson('/api/v1/students/registry/official')
            ->assertStatus(403);
    }
}
