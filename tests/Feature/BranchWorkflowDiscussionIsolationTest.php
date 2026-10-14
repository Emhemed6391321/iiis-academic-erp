<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EnrollmentStatusRequest;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\StudyYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * مناقشات طلبات سير العمل (RequestDiscussion) لا تحمل فرعاً، لذا يجب أن يتحقق
 * الكنترولر من أن الطلب الأصلي يخص فرع المستخدم قبل القراءة أو الكتابة.
 */
class BranchWorkflowDiscussionIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Branch $ownBranch;
    private Branch $branchA;
    private Branch $branchB;
    private Student $otherBranchStudent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $this->manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $this->ownBranch = Branch::where('code', '01')->firstOrFail();
        $this->branchA = Branch::where('code', '02')->firstOrFail();
        $this->branchB = Branch::where('code', '03')->firstOrFail();

        $year = AcademicYear::where('is_current', true)->firstOrFail();
        $this->otherBranchStudent = Student::withoutGlobalScopes()->create([
            'national_id' => '119900000303',
            'branch_id' => $this->branchA->id,
            'department_id' => Department::firstOrFail()->id,
            'current_study_year_id' => StudyYear::firstOrFail()->id,
            'first_name' => 'طالب', 'father_name' => 'أب', 'grandfather_name' => 'جد', 'family_name' => 'اختبار',
            'mother_name' => 'أم', 'gender' => 'MALE', 'birth_date' => '2005-05-01', 'birth_place' => 'طرابلس',
            'phone' => '091-1111111', 'guardian_phone' => '091-2222222', 'study_type' => 'REGULAR',
            'academic_status' => 'ENROLLED_ACTIVE', 'enrolled_academic_year_id' => $year->id,
        ]);
    }

    private function foreignTransfer(): StudentTransfer
    {
        // من فرع إلى فرع آخر، ولا علاقة لفرع مدير الفرع بالطلب
        return StudentTransfer::create([
            'student_id' => $this->otherBranchStudent->id,
            'from_branch_id' => $this->branchA->id,
            'to_branch_id' => $this->branchB->id,
            'status' => 'PENDING',
            'transfer_stage' => 'REQUESTED',
            'reason' => 'طلب نقل اختباري بين فرعين آخرين',
            'requested_by' => $this->manager->id,
        ]);
    }

    public function test_manager_cannot_read_discussion_of_transfer_between_other_branches(): void
    {
        $transfer = $this->foreignTransfer();

        $this->actingAs($this->manager)
            ->getJson("/api/v1/student-workflow/requests/transfer/{$transfer->id}/comments")
            ->assertNotFound();
    }

    public function test_manager_cannot_post_discussion_on_transfer_between_other_branches(): void
    {
        $transfer = $this->foreignTransfer();

        $this->actingAs($this->manager)
            ->postJson("/api/v1/student-workflow/requests/transfer/{$transfer->id}/comments", ['comment_text' => 'تعليق متطفل'])
            ->assertNotFound();

        $this->assertDatabaseMissing('request_discussions', ['comment_text' => 'تعليق متطفل']);
    }

    public function test_manager_cannot_post_discussion_on_status_request_of_other_branch_student(): void
    {
        $statusRequest = EnrollmentStatusRequest::create([
            'student_id' => $this->otherBranchStudent->id,
            'request_type' => 'PAUSE',
            'reason' => 'طلب إيقاف اختباري لطالب فرع آخر',
            'branch_status' => 'PENDING',
            'hq_status' => 'PENDING',
            'final_status' => 'PENDING',
            'created_by' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->postJson("/api/v1/student-workflow/requests/status/{$statusRequest->id}/comments", ['comment_text' => 'تعليق متطفل'])
            ->assertNotFound();

        $this->actingAs($this->manager)
            ->getJson("/api/v1/student-workflow/requests/status/{$statusRequest->id}/comments")
            ->assertNotFound();
    }

    public function test_manager_can_discuss_transfer_that_involves_own_branch(): void
    {
        $transfer = StudentTransfer::create([
            'student_id' => $this->otherBranchStudent->id,
            'from_branch_id' => $this->branchA->id,
            'to_branch_id' => $this->ownBranch->id,
            'status' => 'PENDING',
            'transfer_stage' => 'REQUESTED',
            'reason' => 'طلب نقل اختباري إلى فرعنا',
            'requested_by' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->postJson("/api/v1/student-workflow/requests/transfer/{$transfer->id}/comments", ['comment_text' => 'تعليق من الفرع المستقبِل'])
            ->assertOk();

        $this->actingAs($this->manager)
            ->getJson("/api/v1/student-workflow/requests/transfer/{$transfer->id}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_hq_user_can_still_read_any_transfer_discussion(): void
    {
        $transfer = $this->foreignTransfer();
        $superAdmin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();

        $this->actingAs($superAdmin)
            ->getJson("/api/v1/student-workflow/requests/transfer/{$transfer->id}/comments")
            ->assertOk();
    }
}
