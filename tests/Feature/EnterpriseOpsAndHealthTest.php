<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\SystemAuditTrail;

class EnterpriseOpsAndHealthTest extends TestCase
{
    use RefreshDatabase;

    protected User $hqAdmin;
    protected User $branchUser;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $globalRole = Role::firstOrCreate(['name' => 'super_admin'], [
            'display_name' => 'المدير العام',
            'scope_type'   => 'GLOBAL_SCOPE',
        ]);

        $branchRole = Role::firstOrCreate(['name' => 'branch_user'], [
            'display_name' => 'مستخدم الفرع',
            'scope_type'   => 'BRANCH_SCOPE',
        ]);

        $this->branch = Branch::create([
            'code'    => 'TRIPOLI',
            'name'    => 'فرع طرابلس المركزي',
            'city'    => 'طرابلس',
            'address' => 'طريق الشط',
        ]);

        $this->hqAdmin = User::create([
            'name'        => 'المدير العام',
            'email'       => 'superadmin@iiis.sch.ly',
            'password'    => bcrypt('AdminSecurePass123!'),
            'role_id'     => $globalRole->id,
            'national_id' => '119800000010',
            'phone'       => '091-0000010',
        ]);

        $this->branchUser = User::create([
            'name'        => 'موظف الفرع',
            'email'       => 'branchuser@iiis.sch.ly',
            'password'    => bcrypt('BranchPass123!'),
            'role_id'     => $branchRole->id,
            'branch_id'   => $this->branch->id,
            'national_id' => '119800000011',
            'phone'       => '091-0000011',
        ]);
    }

    public function test_health_check_requires_global_admin_authorization(): void
    {
        // Unauthenticated -> 401
        $guestResponse = $this->getJson('/api/v1/health');
        $guestResponse->assertStatus(401);

        // Branch user -> 403
        $branchResponse = $this->actingAs($this->branchUser)->getJson('/api/v1/health');
        $branchResponse->assertStatus(403);
        $this->assertStringContainsString('غير مصرح', $branchResponse->json('message'));
    }

    public function test_health_check_returns_healthy_for_global_admin(): void
    {
        // Log an audit trail entry to ensure chain exists
        SystemAuditTrail::log(
            eventType: 'HEALTH_CHECK_TEST',
            description: 'فحص دوري للمنظومة',
            payload: ['status' => 'testing'],
            userId: $this->hqAdmin->id,
            branchId: $this->branch->id
        );

        $response = $this->actingAs($this->hqAdmin)->getJson('/api/v1/health');

        $response->assertOk();
        $response->assertJson([
            'status'  => 'HEALTHY',
            'version' => '2.0.0-ENTERPRISE',
        ]);

        $data = $response->json();
        $this->assertEquals('UP', $data['components']['database']['status']);
        $this->assertEquals('UP', $data['components']['cache']['status']);
        $this->assertEquals('UP', $data['components']['vault_storage']['status']);
        $this->assertEquals('INTACT', $data['components']['audit_chain']['status']);
    }

    public function test_enterprise_backup_artisan_command_generates_snapshot_and_manifest(): void
    {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('backup:enterprise-snapshot', ['--verify' => true]);
        $this->assertEquals(0, $exitCode);

        // Verify backups directory exists
        $backupsDir = storage_path('app/backups');
        $this->assertTrue(File::exists($backupsDir));

        $snapshots = File::directories($backupsDir);
        $this->assertNotEmpty($snapshots);

        $latestSnapshot = end($snapshots);
        $this->assertTrue(File::exists("{$latestSnapshot}/manifest.json"));
        $this->assertTrue(File::exists("{$latestSnapshot}/manifest.sha256"));

        $manifestContent = json_decode(File::get("{$latestSnapshot}/manifest.json"), true);
        $this->assertEquals('2.0.0-ENTERPRISE', $manifestContent['system_version']);
        $this->assertTrue($manifestContent['audit_chain']['intact']);
    }
}
