<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\User;
use App\Services\StudentTransferWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * خطوات النقل كما ترسلها نافذة المصافحة في الواجهة (central_memo / receiving_decision).
 */
class TransferStepFromModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);
    }

    public function test_modal_payloads_advance_the_transfer_through_both_steps(): void
    {
        $manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $admin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();
        $student = Student::where('branch_id', $manager->branch_id)->firstOrFail();
        $target = Branch::where('id', '!=', $manager->branch_id)->where('is_active', true)->firstOrFail();

        $transfer = app(StudentTransferWorkflow::class)->initiateTransfer($student, $target->id, 'تغيير مكان السكن', $manager);

        // المرحلة الأولى: توصية الشؤون الأكاديمية بالموافقة
        $this->actingAs($admin)->postJson("/api/v1/student-workflow/transfer-request/{$transfer->id}/step", [
            'step' => 'central_memo',
            'memo' => 'تمت دراسة الطلب والتوصية بالموافقة.',
            'action' => 'RECOMMEND_APPROVE',
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertSame('ACADEMIC_AFFAIRS_REVIEWED', $transfer->fresh()->transfer_stage);

        // المرحلة الثانية: قرار الفرع المستقبل بالموافقة
        $this->actingAs($admin)->postJson("/api/v1/student-workflow/transfer-request/{$transfer->id}/step", [
            'step' => 'receiving_decision',
            'decision' => 'APPROVED',
            'notes' => 'تم تأكيد الضم.',
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertSame(StudentTransferWorkflow::STAGE_DESTINATION_ACCEPTED, $transfer->fresh()->transfer_stage);
    }

    public function test_recommend_reject_in_memo_step_rejects_the_transfer(): void
    {
        $manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $admin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();
        $student = Student::where('branch_id', $manager->branch_id)->firstOrFail();
        $target = Branch::where('id', '!=', $manager->branch_id)->where('is_active', true)->firstOrFail();

        $transfer = app(StudentTransferWorkflow::class)->initiateTransfer($student, $target->id, 'سبب', $manager);

        $this->actingAs($admin)->postJson("/api/v1/student-workflow/transfer-request/{$transfer->id}/step", [
            'step' => 'central_memo',
            'memo' => 'لا توجد مبررات كافية.',
            'action' => 'RECOMMEND_REJECT',
        ])->assertOk();

        $this->assertSame(StudentTransferWorkflow::STAGE_REJECTED, $transfer->fresh()->transfer_stage);
    }

    public function test_unknown_step_is_still_rejected(): void
    {
        $admin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();

        $this->actingAs($admin)->postJson('/api/v1/student-workflow/transfer-request/999/step', [
            'step' => 'something_else',
        ])->assertStatus(404);
    }
}
