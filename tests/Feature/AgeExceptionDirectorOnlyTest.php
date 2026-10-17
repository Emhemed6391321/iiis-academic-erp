<?php

namespace Tests\Feature;

use App\Models\AdministrativeRequest;
use App\Models\Branch;
use App\Models\EmployeePlacement;
use App\Models\JobPosition;
use App\Models\Role;
use App\Models\StudyYear;
use App\Models\User;
use App\Services\AdministrativeRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An age exception is decided by the institute director (or the super admin) only.
 * Other HQ officers keep deciding the grade-level requests they were already allowed to decide.
 */
class AgeExceptionDirectorOnlyTest extends TestCase
{
    use RefreshDatabase;

    private User $registrar;
    private User $hqOfficer;
    private User $director;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $this->registrar = User::where('email', 'registrar.tip@iiis.sch.ly')->firstOrFail();
        $this->branch = Branch::where('city', 'طرابلس')->firstOrFail();

        // Holds students.approve_hq (the HQ approval permission) but is not the institute director.
        $this->hqOfficer = User::factory()->create([
            'role_id' => Role::where('name', 'hq_student_affairs')->firstOrFail()->id,
            'branch_id' => null,
            'must_change_password' => false,
        ]);

        $directorPosition = JobPosition::firstOrCreate(
            ['code' => AdministrativeRequestService::DIRECTOR_POSITION_CODE],
            ['title' => 'المدير العام للمعهد', 'level_order' => 1, 'is_active' => true]
        );
        $this->director = User::factory()->create([
            'role_id' => Role::where('name', 'hq_student_affairs')->firstOrFail()->id,
            'branch_id' => null,
            'must_change_password' => false,
        ]);
        EmployeePlacement::create([
            'user_id' => $this->director->id,
            'job_position_id' => $directorPosition->id,
            'branch_id' => $this->branch->id,
            'start_date' => '2026-09-01',
            'is_current' => true,
            'status' => 'active',
            'show_name_on_documents' => true,
        ]);
    }

    private function submitAgeException(): int
    {
        return $this->actingAs($this->registrar)->postJson('/api/v1/admin-requests', [
            'request_type' => AdministrativeRequest::TYPE_AGE_EXCEPTION,
            'subject_name' => 'سالم الفزاني',
            'national_id' => '120' . random_int(100000000, 999999999),
            'justification' => 'طالب متفوق ومزكى من مجلس الفرع.',
        ])->assertStatus(201)->json('request.id');
    }

    public function test_hq_officer_cannot_decide_an_age_exception_even_with_approval_permission(): void
    {
        $requestId = $this->submitAgeException();

        $this->actingAs($this->hqOfficer)->postJson("/api/v1/admin-requests/{$requestId}/decide", [
            'outcome' => 'APPROVED',
        ])->assertStatus(403);

        $this->assertSame('PENDING_HQ', AdministrativeRequest::find($requestId)->status);
        $this->assertNull(AdministrativeRequest::find($requestId)->decided_at);

        $listed = collect($this->actingAs($this->hqOfficer)->getJson('/api/v1/admin-requests')->json('requests'))
            ->firstWhere('id', $requestId);
        $this->assertFalse($listed['can_decide']);
    }

    public function test_institute_director_decides_an_age_exception(): void
    {
        $requestId = $this->submitAgeException();

        $listed = collect($this->actingAs($this->director)->getJson('/api/v1/admin-requests')->json('requests'))
            ->firstWhere('id', $requestId);
        $this->assertTrue($listed['can_decide']);

        $this->actingAs($this->director)->postJson("/api/v1/admin-requests/{$requestId}/decide", [
            'outcome' => 'APPROVED',
        ])->assertOk();

        $this->assertSame('APPROVED', AdministrativeRequest::find($requestId)->status);
    }

    public function test_hq_officer_still_decides_a_grade_level_request(): void
    {
        $secondYear = StudyYear::where('level_order', 2)->firstOrFail();
        $requestId = $this->actingAs($this->registrar)->postJson('/api/v1/admin-requests', [
            'request_type' => AdministrativeRequest::TYPE_GRADE_LEVEL,
            'subject_name' => 'يوسف الزليتني',
            'national_id' => '120' . random_int(100000000, 999999999),
            'study_year_id' => $secondYear->id,
            'justification' => 'طالب انتقل من معهد آخر بكشف معتمد.',
        ])->assertStatus(201)->json('request.id');

        $this->actingAs($this->hqOfficer)->postJson("/api/v1/admin-requests/{$requestId}/decide", [
            'outcome' => 'APPROVED',
        ])->assertOk();

        $this->assertSame('APPROVED', AdministrativeRequest::find($requestId)->status);
    }
}
