<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Services\SystemErrorMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemErrorMonitoringSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
    }

    public function test_super_admin_can_access_system_error_monitoring(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام الرئيسي', 'is_global' => true]);
        $superAdmin = User::factory()->create(['role_id' => $role->id]);

        $res = $this->actingAs($superAdmin)->getJson('/api/v1/system-errors');
        $res->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_branch_manager_is_strictly_forbidden_from_error_monitoring(): void
    {
        $branch = Branch::first();
        $role = Role::firstOrCreate(['name' => 'branch_manager'], ['display_name' => 'مدير فرع', 'is_global' => false]);
        $branchManager = User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);

        $res = $this->actingAs($branchManager)->getJson('/api/v1/system-errors');
        $res->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_error_service_captures_page_and_button_action_context(): void
    {
        $service = app(SystemErrorMonitoringService::class);
        $log = $service->recordLog([
            'message' => 'حدث خطأ أثناء اعتماد العقد',
            'page_context' => 'العقود والمقرات',
            'button_action' => 'اعتماد وسريان العقد',
            'severity' => 'HIGH',
        ]);

        $this->assertNotNull($log);
        $this->assertDatabaseHas('system_error_logs', [
            'id' => $log->id,
            'page_context' => 'العقود والمقرات',
            'button_action' => 'اعتماد وسريان العقد',
        ]);
    }
}
