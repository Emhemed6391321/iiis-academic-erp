<?php

namespace Tests\Feature;

use App\Models\BackupLedger;
use App\Models\Role;
use App\Models\SystemAuditTrail;
use App\Models\User;
use App\Services\EnterpriseBackupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EnterpriseBackupSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
    }

    protected function createSuperAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام الرئيسي', 'is_global' => true]);
        return User::factory()->create(['role_id' => $role->id]);
    }

    protected function createBranchUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'branch_user'], ['display_name' => 'موظف فرع', 'is_global' => false]);
        return User::factory()->create(['role_id' => $role->id, 'branch_id' => 1]);
    }

    public function test_super_admin_can_list_backups(): void
    {
        $admin = $this->createSuperAdmin();

        BackupLedger::create([
            'disk'                 => 'local',
            'file_name'            => 'IIIS_TEST_BACKUP.enc',
            'file_path'            => 'backups/encrypted/IIIS_TEST_BACKUP.enc',
            'file_size_bytes'      => 1024000,
            'sha256_checksum'      => hash('sha256', 'dummy_content'),
            'type'                 => 'manual',
            'status'               => 'completed',
            'is_encrypted'         => true,
            'encryption_algorithm' => 'AES-256-CBC',
            'database_driver'      => 'sqlite',
        ]);

        $res = $this->actingAs($admin)->getJson('/api/v1/backups');
        $res->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'data',
                'pagination',
                'summary' => [
                    'total_completed_backups',
                    'total_storage_human',
                    'encryption_standard',
                ]
            ]);
    }

    public function test_branch_user_is_strictly_forbidden_from_backups(): void
    {
        $branchUser = $this->createBranchUser();

        $res = $this->actingAs($branchUser)->getJson('/api/v1/backups');
        $res->assertStatus(403);

        $createRes = $this->actingAs($branchUser)->postJson('/api/v1/backups/manual');
        $createRes->assertStatus(403);
    }

    public function test_can_create_manual_backup_synchronously(): void
    {
        $admin = $this->createSuperAdmin();

        $res = $this->actingAs($admin)->postJson('/api/v1/backups/manual', ['sync' => true]);
        $res->assertStatus(201)
            ->assertJson(['status' => 'success']);

        $backupId = $res->json('data.id');
        $ledger = BackupLedger::findOrFail($backupId);

        $this->assertEquals('completed', $ledger->status);
        $this->assertEquals('manual', $ledger->type);
        $this->assertTrue($ledger->is_encrypted);
        $this->assertEquals('AES-256-CBC', $ledger->encryption_algorithm);
        $this->assertNotEmpty($ledger->sha256_checksum);
        $this->assertGreaterThan(0, $ledger->file_size_bytes);

        // Verify physical file exists and checksum matches
        $integrity = $ledger->verifyIntegrity();
        $this->assertTrue($integrity['valid']);
        $this->assertEquals('VERIFIED', $integrity['status']);

        // Verify audit log
        $this->assertDatabaseHas('system_audit_trails', [
            'event_type' => 'BACKUP_CREATED',
        ]);
    }

    public function test_can_download_and_verify_backup(): void
    {
        $admin = $this->createSuperAdmin();

        // Create a real backup
        $ledger = EnterpriseBackupService::createBackup('manual', $admin->id);

        $res = $this->actingAs($admin)->get("/api/v1/backups/{$ledger->id}/download");
        $res->assertStatus(200);
        $res->assertHeader('Content-Disposition');
        $res->assertHeader('X-SHA256-Checksum', $ledger->sha256_checksum);

        // Verify audit log
        $this->assertDatabaseHas('system_audit_trails', [
            'event_type' => 'BACKUP_DOWNLOADED',
        ]);
    }

    public function test_tampered_file_fails_verification(): void
    {
        $admin = $this->createSuperAdmin();
        $ledger = EnterpriseBackupService::createBackup('manual', $admin->id);

        $fullPath = storage_path('app/' . $ledger->file_path);
        // Tamper with file
        File::append($fullPath, 'MALICIOUS_TAMPERED_DATA');

        $integrity = $ledger->verifyIntegrity();
        $this->assertFalse($integrity['valid']);
        $this->assertEquals('CHECKSUM_MISMATCH', $integrity['status']);

        // Verify API also reports failure
        $res = $this->actingAs($admin)->getJson("/api/v1/backups/{$ledger->id}/verify");
        $res->assertStatus(200)
            ->assertJson(['status' => 'error'])
            ->assertJsonPath('data.status', 'CHECKSUM_MISMATCH');
    }

    public function test_can_restore_from_backup(): void
    {
        $admin = $this->createSuperAdmin();
        $ledger = EnterpriseBackupService::createBackup('manual', $admin->id);

        $restoreRes = $this->actingAs($admin)->postJson("/api/v1/backups/{$ledger->id}/restore");
        $restoreRes->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonPath('data.restored_database', true);
    }
}
