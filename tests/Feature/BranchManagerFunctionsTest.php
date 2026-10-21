<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\BranchRequest;
use App\Models\Department;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * وظائف مدير الفرع من طرف الواجهة إلى الخادم: ما يُمنح له يعمل على فرعه فقط،
 * وما لا يُمنح له (إدارة الفروع والعقود والاعتماد) يُرفض حتى مع معرفة المعرّف.
 */
class BranchManagerFunctionsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Branch $ownBranch;
    private Branch $otherBranch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $this->manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $this->ownBranch = Branch::where('code', '01')->firstOrFail();
        $this->otherBranch = Branch::where('code', '02')->firstOrFail();
    }

    private function ticket(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $this->ownBranch->id,
            'category' => 'MAINTENANCE',
            'priority' => 'medium',
            'title' => 'عطل في المكيف',
            'description' => 'المكيف في القاعة الأولى لا يعمل',
        ], $overrides);
    }

    public function test_manager_opens_a_maintenance_ticket_and_it_always_lands_on_own_branch(): void
    {
        $this->actingAs($this->manager)->postJson('/api/v1/branches/requests', $this->ticket([
            'branch_id' => $this->otherBranch->id,
        ]))->assertSuccessful();

        $this->assertDatabaseHas('branch_requests', [
            'title' => 'عطل في المكيف',
            'branch_id' => $this->ownBranch->id,
        ]);
        $this->assertDatabaseMissing('branch_requests', ['branch_id' => $this->otherBranch->id]);
    }

    public function test_manager_sees_the_tickets_of_own_branch_but_cannot_change_their_status(): void
    {
        $this->actingAs($this->manager)->postJson('/api/v1/branches/requests', $this->ticket())->assertSuccessful();
        $ticket = BranchRequest::where('branch_id', $this->ownBranch->id)->firstOrFail();

        $this->actingAs($this->manager)->getJson('/api/v1/branches/requests')
            ->assertOk()
            ->assertJsonFragment(['title' => 'عطل في المكيف']);

        // Closing or starting a ticket belongs to the central maintenance team (branches.manage).
        $this->actingAs($this->manager)->patchJson("/api/v1/branches/requests/{$ticket->id}/status", ['status' => 'completed'])
            ->assertForbidden();
        $this->assertNotSame('completed', $ticket->fresh()->status);
    }

    public function test_manager_reads_contracts_but_cannot_register_approve_or_delete_them(): void
    {
        $this->actingAs($this->manager)->getJson('/api/v1/contracts')->assertOk();

        $this->actingAs($this->manager)->postJson('/api/v1/contracts', [])->assertForbidden();
        $this->actingAs($this->manager)->postJson('/api/v1/contracts/1/approve')->assertForbidden();
        $this->actingAs($this->manager)->deleteJson('/api/v1/contracts/1')->assertForbidden();
    }

    public function test_manager_cannot_decide_administrative_requests(): void
    {
        $this->actingAs($this->manager)->postJson('/api/v1/admin-requests/1/decide', ['decision' => 'APPROVED'])
            ->assertForbidden();
    }

    public function test_manager_reads_own_branch_overview_and_directory_only(): void
    {
        $this->actingAs($this->manager)->getJson('/api/v1/branches/overview')->assertOk();

        $directory = collect($this->actingAs($this->manager)->getJson('/api/v1/branches/directory')->assertOk()->json('data'));
        $this->assertTrue($directory->contains('id', $this->ownBranch->id));
    }

    public function test_manager_reads_attendance_dashboard_and_at_risk_list_for_own_branch(): void
    {
        $this->actingAs($this->manager)->getJson('/api/v1/attendance/dashboard-stats')->assertOk();
        $this->actingAs($this->manager)->getJson('/api/v1/attendance/at-risk-students')->assertOk();
    }

    public function test_manager_exports_the_registry_of_own_branch(): void
    {
        $this->actingAs($this->manager)->get('/api/v1/students/registry/export')
            ->assertOk();
    }

    public function test_manager_registers_a_student_only_into_own_branch(): void
    {
        $start = AcademicYear::where('is_current', true)->firstOrFail()->start_date;

        $this->actingAs($this->manager)->postJson('/api/v1/students', [
            'nationality_category' => 'LIBYAN',
            'national_id' => '120555000111',
            'first_name' => 'محمد', 'father_name' => 'علي', 'grandfather_name' => 'أحمد', 'family_name' => 'الورفلي',
            'mother_name' => 'فاطمة محمود',
            'gender' => 'MALE',
            'birth_date' => \Carbon\Carbon::parse($start)->subYears(16)->format('Y-m-d'),
            'birth_place' => 'طرابلس',
            'phone' => '091-1112233', 'guardian_phone' => '091-2223344',
            'department_id' => Department::firstOrFail()->id,
            'current_study_year_id' => StudyYear::firstOrFail()->id,
            'study_type' => 'REGULAR',
            'branch_id' => $this->otherBranch->id,
        ])->assertSuccessful();

        $this->assertDatabaseHas('students', ['national_id' => '120555000111', 'branch_id' => $this->ownBranch->id]);
    }

    /** A student of the manager's own branch, ready to be transferred. */
    private function ownStudent(): Student
    {
        return Student::withoutGlobalScopes()->create([
            'national_id' => '120777000333',
            'branch_id' => $this->ownBranch->id,
            'department_id' => Department::firstOrFail()->id,
            'current_study_year_id' => StudyYear::firstOrFail()->id,
            'first_name' => 'طالب', 'father_name' => 'أب', 'grandfather_name' => 'جد', 'family_name' => 'النقل', 'mother_name' => 'أم',
            'gender' => 'MALE', 'birth_date' => '2005-05-01', 'birth_place' => 'طرابلس',
            'phone' => '091-1111111', 'guardian_phone' => '091-2222222',
            'study_type' => 'REGULAR', 'academic_status' => 'ENROLLED_ACTIVE',
            'enrolled_academic_year_id' => AcademicYear::where('is_current', true)->firstOrFail()->id,
        ]);
    }

    /** The manager of the receiving branch (branch 02). */
    private function destinationManager(): User
    {
        $role = \App\Models\Role::where('name', 'branch_manager')->firstOrFail();

        return User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $this->otherBranch->id,
            'must_change_password' => false,
        ]);
    }

    public function test_manager_transfer_from_the_student_file_is_a_request_and_does_not_move_the_student(): void
    {
        $student = $this->ownStudent();

        $this->actingAs($this->manager)->postJson("/api/v1/students/{$student->id}/file/transfer", [
            'to_branch_id' => $this->otherBranch->id,
            'reason' => 'انتقال الطالب بسبب تغيير مكان السكن',
        ])->assertStatus(202)->assertJsonPath('success', true);

        $this->assertSame($this->ownBranch->id, $student->fresh()->branch_id);
        $this->assertDatabaseHas('student_transfers', [
            'student_id' => $student->id,
            'from_branch_id' => $this->ownBranch->id,
            'to_branch_id' => $this->otherBranch->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_transfer_moves_the_student_only_after_receiving_branch_and_hq_approve(): void
    {
        $student = $this->ownStudent();
        $admin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();
        $destination = $this->destinationManager();

        $transferId = $this->actingAs($this->manager)->postJson("/api/v1/students/{$student->id}/file/transfer", [
            'to_branch_id' => $this->otherBranch->id,
            'reason' => 'انتقال الطالب بسبب تغيير مكان السكن',
        ])->assertStatus(202)->json('transfer_id');

        $step = fn (User $who, array $body) => $this->actingAs($who)
            ->postJson("/api/v1/student-workflow/transfer-request/{$transferId}/step", $body);

        // Central academic affairs reviews first. The branch manager cannot do this step.
        $step($this->manager, ['step' => 'central_memo', 'memo' => 'محاولة', 'action' => 'RECOMMEND_APPROVE'])->assertForbidden();
        $step($admin, ['step' => 'central_memo', 'memo' => 'لا مانع من النقل', 'action' => 'RECOMMEND_APPROVE'])->assertOk();

        // The origin manager cannot accept an incoming student: only the receiving branch decides.
        $step($this->manager, ['step' => 'receiving_decision', 'decision' => 'APPROVED', 'notes' => 'محاولة'])->assertForbidden();
        $this->assertSame($this->ownBranch->id, $student->fresh()->branch_id);

        // The receiving branch accepts. The student is still in the origin branch until HQ approves.
        $step($destination, ['step' => 'receiving_decision', 'decision' => 'APPROVED', 'notes' => 'توفر السعة'])->assertOk();
        $this->assertSame($this->ownBranch->id, $student->fresh()->branch_id);

        // The origin manager cannot give the final approval, and HQ gives it.
        $step($this->manager, ['step' => 'hq_final', 'notes' => 'محاولة'])->assertForbidden();
        $step($admin, ['step' => 'hq_final', 'notes' => 'اعتماد النقل'])->assertOk();

        $this->assertSame($this->otherBranch->id, $student->fresh()->branch_id);
    }

    public function test_manager_cannot_request_a_transfer_for_a_student_of_another_branch(): void
    {
        $other = Student::withoutGlobalScopes()->create([
            'national_id' => '120888000444',
            'branch_id' => $this->otherBranch->id,
            'department_id' => Department::firstOrFail()->id,
            'current_study_year_id' => StudyYear::firstOrFail()->id,
            'first_name' => 'طالب', 'father_name' => 'أب', 'grandfather_name' => 'جد', 'family_name' => 'آخر', 'mother_name' => 'أم',
            'gender' => 'MALE', 'birth_date' => '2005-05-01', 'birth_place' => 'طرابلس',
            'phone' => '091-1111111', 'guardian_phone' => '091-2222222',
            'study_type' => 'REGULAR', 'academic_status' => 'ENROLLED_ACTIVE',
            'enrolled_academic_year_id' => AcademicYear::where('is_current', true)->firstOrFail()->id,
        ]);

        // The branch scope hides the student, so the manager cannot even reach the transfer.
        $this->actingAs($this->manager)->postJson("/api/v1/students/{$other->id}/file/transfer", [
            'to_branch_id' => $this->ownBranch->id,
            'reason' => 'محاولة نقل طالب من فرع آخر',
        ])->assertNotFound();
    }

    public function test_manager_cannot_open_the_file_of_a_student_in_another_branch(): void
    {
        $other = Student::withoutGlobalScopes()->create([
            'national_id' => '119900000202',
            'branch_id' => $this->otherBranch->id,
            'department_id' => Department::firstOrFail()->id,
            'current_study_year_id' => StudyYear::firstOrFail()->id,
            'first_name' => 'طالب', 'father_name' => 'أب', 'grandfather_name' => 'جد', 'family_name' => 'اختبار', 'mother_name' => 'أم',
            'gender' => 'MALE', 'birth_date' => '2005-05-01', 'birth_place' => 'طرابلس',
            'phone' => '091-1111111', 'guardian_phone' => '091-2222222',
            'study_type' => 'REGULAR', 'academic_status' => 'ENROLLED_ACTIVE',
            'enrolled_academic_year_id' => AcademicYear::where('is_current', true)->firstOrFail()->id,
        ]);

        // The branch scope hides the record, so the manager gets "not found" and learns nothing about it.
        $this->actingAs($this->manager)->getJson("/api/v1/students/{$other->id}/file")
            ->assertNotFound();
    }
}
