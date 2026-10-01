<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchContract;
use App\Models\Property;
use App\Models\User;
use App\Models\Role;
use App\Models\Student;
use App\Services\ContractFinancialEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyContractFinancialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
    }

    public function test_can_create_and_list_properties(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام', 'is_global' => true]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $payload = [
            'name' => 'مبنى الإدارة والمدرجات المركزية',
            'type' => 'BUILDING',
            'city' => 'طرابلس',
            'region' => 'النوفليين',
            'area_sqm' => 850,
            'floors_count' => 3,
            'halls_count' => 8,
            'offices_count' => 6,
            'property_status' => 'READY',
            'usage_status' => 'OCCUPIED',
            'branch_id' => 1,
        ];

        $res = $this->actingAs($user)->postJson('/api/v1/properties', $payload);
        $res->assertStatus(201)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('properties', [
            'name' => 'مبنى الإدارة والمدرجات المركزية',
            'city' => 'طرابلس',
            'branch_id' => 1,
        ]);

        $listRes = $this->actingAs($user)->getJson('/api/v1/properties');
        $listRes->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }

    public function test_contract_lifecycle_and_installments(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام', 'is_global' => true]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $property = Property::create([
            'property_number' => 'PROP-2026-TEST',
            'name' => 'مقر المعهد - فرع بنغازي',
            'city' => 'بنغازي',
            'branch_id' => 2,
            'property_status' => 'READY',
            'usage_status' => 'OCCUPIED',
        ]);

        $contractPayload = [
            'title' => 'عقد إيجار مقر بنغازي السنوي',
            'contract_type' => 'إيجار مقر',
            'branch_id' => 2,
            'property_id' => $property->id,
            'contractor_name' => 'شركة البناء والعقارات',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'duration_months' => 12,
            'total_value' => 60000,
            'payment_frequency' => 'MONTHLY',
        ];

        $createRes = $this->actingAs($user)->postJson('/api/v1/contracts', $contractPayload);
        $createRes->assertStatus(201);

        $contractId = $createRes->json('data.id');
        $this->assertDatabaseHas('branch_contracts', [
            'id' => $contractId,
            'status' => 'DRAFT',
        ]);

        // 12 installments generated
        $this->assertDatabaseCount('contract_installments', 12);

        // Approve
        $approveRes = $this->actingAs($user)->postJson("/api/v1/contracts/{$contractId}/approve");
        $approveRes->assertStatus(200)
            ->assertJsonPath('data.status', 'ACTIVE');

        // Suspend
        $suspendRes = $this->actingAs($user)->postJson("/api/v1/contracts/{$contractId}/suspend", [
            'suspension_reason' => 'صيانة طارئة في شبكة الصرف',
        ]);
        $suspendRes->assertStatus(200)
            ->assertJsonPath('data.status', 'SUSPENDED');

        // Reactivate
        $activateRes = $this->actingAs($user)->postJson("/api/v1/contracts/{$contractId}/activate");
        $activateRes->assertStatus(200)
            ->assertJsonPath('data.status', 'ACTIVE');

        // Record a Payment
        $payRes = $this->actingAs($user)->postJson("/api/v1/contracts/{$contractId}/payments", [
            'amount' => 5000,
            'payment_reference' => 'CHQ-998811',
        ]);
        $payRes->assertStatus(200);

        $this->assertDatabaseHas('branch_contracts', [
            'id' => $contractId,
            'paid_value' => 5000,
        ]);

        // Terminate
        $termRes = $this->actingAs($user)->postJson("/api/v1/contracts/{$contractId}/terminate", [
            'termination_reason' => 'انتهاء المدة المتفق عليها',
            'remaining_obligations' => 0,
        ]);
        $termRes->assertStatus(200)
            ->assertJsonPath('data.status', 'TERMINATED');
    }

    public function test_financial_engine_statements_and_alerts(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام', 'is_global' => true]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $service = app(ContractFinancialEngineService::class);
        $central = $service->getCentralFinancialStatement();

        $this->assertIsArray($central);
        $this->assertArrayHasKey('total_properties_count', $central);
        $this->assertArrayHasKey('total_annual_contracts_value', $central);
        $this->assertArrayHasKey('cost_per_branch', $central);

        $alerts = $service->getAutomatedContractAlerts();
        $this->assertIsArray($alerts);

        $res = $this->actingAs($user)->getJson('/api/v1/financial/central');
        $res->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['total_properties_count', 'total_annual_contracts_value']]);
    }

    public function test_safe_delete_branch_blocks_when_students_exist(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام', 'is_global' => true]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $branch = Branch::first();
        $student = Student::first();
        $student->update(['branch_id' => $branch->id]);

        $res = $this->actingAs($user)->deleteJson("/api/v1/branches/{$branch->id}");
        $res->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'action_taken' => 'STATUS_SET_TO_CLOSED',
            ]);

        // Branch was not deleted, but changed to CLOSED
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'branch_status' => 'CLOSED',
        ]);
    }
}
