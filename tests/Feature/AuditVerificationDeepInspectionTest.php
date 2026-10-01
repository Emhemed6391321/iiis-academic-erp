<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\Property;
use App\Models\BranchContract;
use App\Models\SystemAuditTrail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditVerificationDeepInspectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $branch1Manager;
    protected User $branch2Manager;
    protected User $hqAdmin;
    protected Branch $branch1;
    protected Branch $branch2;
    protected BranchContract $contractBranch1;

    protected function setUp(): void
    {
        parent::setUp();

        $roleHq = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير عام', 'scope_type' => 'GLOBAL_SCOPE']);
        $roleBranch = Role::firstOrCreate(['name' => 'branch_manager'], ['display_name' => 'مدير فرع', 'scope_type' => 'BRANCH_SCOPE']);

        $this->branch1 = Branch::create([
            'name' => 'فرع طرابلس المركزي',
            'code' => 'TIP-MAIN',
            'city' => 'طرابلس',
            'branch_status' => 'ACTIVE',
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'name' => 'فرع بنغازي',
            'code' => 'BEN-01',
            'city' => 'بنغازي',
            'branch_status' => 'ACTIVE',
            'is_active' => true,
        ]);

        $this->branch1Manager = User::factory()->create([
            'role_id' => $roleBranch->id,
            'branch_id' => $this->branch1->id,
        ]);

        $this->branch2Manager = User::factory()->create([
            'role_id' => $roleBranch->id,
            'branch_id' => $this->branch2->id,
        ]);

        $this->hqAdmin = User::factory()->create([
            'role_id' => $roleHq->id,
            'branch_id' => null,
        ]);

        $this->contractBranch1 = BranchContract::create([
            'title' => 'عقد مقر طرابلس الإداري',
            'contract_number' => 'CNT-2026-TEST1',
            'contract_type' => 'LEASE',
            'branch_id' => $this->branch1->id,
            'contractor_name' => 'شركة المباني العامة',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'total_value' => 60000.00,
            'payment_frequency' => 'MONTHLY',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_branch_user_cannot_view_or_modify_other_branch_contract(): void
    {
        // Branch 2 Manager trying to view Branch 1 contract:
        // The SQL BranchScope isolates the query at the database level so the record is completely invisible (404) or blocked (403).
        $response = $this->actingAs($this->branch2Manager)->getJson("/api/v1/contracts/{$this->contractBranch1->id}");
        $this->assertTrue(in_array($response->status(), [403, 404]), "Status should be 403 or 404, got {$response->status()}");

        // Branch 2 Manager trying to suspend Branch 1 contract
        $suspendResponse = $this->actingAs($this->branch2Manager)->postJson("/api/v1/contracts/{$this->contractBranch1->id}/suspend", [
            'suspension_reason' => 'محاولة إيقاف غير مصرح بها',
        ]);
        $this->assertTrue(in_array($suspendResponse->status(), [403, 404]), "Status should be 403 or 404, got {$suspendResponse->status()}");
    }

    public function test_branch_user_cannot_access_central_statement(): void
    {
        $response = $this->actingAs($this->branch1Manager)->getJson('/api/v1/financial/central');
        $response->assertStatus(403);

        // HQ Admin can access central statement
        $hqResponse = $this->actingAs($this->hqAdmin)->getJson('/api/v1/financial/central');
        $hqResponse->assertStatus(200);
    }

    public function test_branch_suspension_and_contract_payment_logs_audit_trail(): void
    {
        // Suspend branch
        $response = $this->actingAs($this->hqAdmin)->postJson("/api/v1/branches/{$this->branch1->id}/suspend", [
            'suspension_reason' => 'أعمال صيانة وتجديد مقرات شاملة',
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('system_audit_trails', [
            'event_type' => 'BRANCH_SUSPENDED',
            'branch_id' => $this->branch1->id,
        ]);

        // Record contract payment
        $payResponse = $this->actingAs($this->hqAdmin)->postJson("/api/v1/contracts/{$this->contractBranch1->id}/payments", [
            'amount' => 5000.00,
            'payment_reference' => 'CHQ-998822',
        ]);
        $payResponse->assertStatus(200);

        $this->assertDatabaseHas('system_audit_trails', [
            'event_type' => 'CONTRACT_PAYMENT_RECORDED',
            'branch_id' => $this->branch1->id,
        ]);
    }

    public function test_shielded_routes_blocked_for_external_callers(): void
    {
        $response = $this->actingAs($this->hqAdmin)
            ->withHeader('X-Simulate-External', '1')
            ->getJson('/api/v1/exams/pending-batches');

        $response->assertStatus(423)
            ->assertJson([
                'status' => 'shielded',
                'feature' => 'STUDY_AND_EXAMS_SHIELDED',
            ]);
    }
}
