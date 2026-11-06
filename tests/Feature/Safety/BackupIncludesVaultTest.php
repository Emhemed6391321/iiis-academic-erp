<?php

namespace Tests\Feature\Safety;

use App\Models\Role;
use App\Models\User;
use App\Services\EnterpriseBackupService;
use App\Services\SecureFileVaultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

/**
 * Hotfix 2.5.9: the encrypted vault is written to the `local` disk
 * (storage/app/private/secure_vault) but backups used to read
 * storage/app/secure_vault, so vault files were never backed up or restored.
 */
class BackupIncludesVaultTest extends TestCase
{
    use RefreshDatabase;

    private string $marker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        $this->marker = '__hotfix_test_' . Str::random(8);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(Storage::disk('local')->path('secure_vault/' . $this->marker));
        File::deleteDirectory(storage_path('app/secure_vault/' . $this->marker));
        parent::tearDown();
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام الرئيسي', 'is_global' => true]);
        return User::factory()->create(['role_id' => $role->id]);
    }

    /** Writes a vault file exactly where SecureFileVaultService stores files. */
    private function putVaultFile(string $content): string
    {
        $relative = "secure_vault/{$this->marker}/branch_1/" . Str::uuid() . '.pdf.enc';
        Storage::disk('local')->put($relative, $content);
        return $relative;
    }

    private function extractBackup(string $encryptedRelativePath): string
    {
        $dir = storage_path('app/backups/test_extract_' . uniqid());
        File::makeDirectory($dir, 0750, true);
        EnterpriseBackupService::decryptFile(storage_path('app/' . $encryptedRelativePath), "{$dir}/payload.zip");
        $zip = new ZipArchive();
        $this->assertTrue($zip->open("{$dir}/payload.zip") === true);
        $zip->extractTo($dir);
        $zip->close();
        return $dir;
    }

    public function test_vault_service_writes_under_the_local_disk_vault_path(): void
    {
        $this->assertSame(
            str_replace('\\', '/', Storage::disk('local')->path('secure_vault')),
            str_replace('\\', '/', \App\Support\SecureVaultPaths::primary())
        );
        $this->assertTrue(class_exists(SecureFileVaultService::class));
    }

    public function test_backup_archive_contains_vault_files_with_matching_hash(): void
    {
        $content = 'ENCRYPTED-' . Str::random(64);
        $relative = $this->putVaultFile($content);

        $ledger = EnterpriseBackupService::createBackup('manual', $this->admin()->id);
        $dir = $this->extractBackup($ledger->file_path);

        try {
            $inArchive = "{$dir}/storage_files/" . $relative;
            $this->assertFileExists($inArchive, 'Vault file missing from backup archive');
            $this->assertSame(hash('sha256', $content), hash_file('sha256', $inArchive));
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function test_backup_still_packs_legacy_vault_directory(): void
    {
        $legacyFile = storage_path("app/secure_vault/{$this->marker}/legacy.enc");
        File::ensureDirectoryExists(dirname($legacyFile));
        File::put($legacyFile, 'LEGACY');

        $ledger = EnterpriseBackupService::createBackup('manual', $this->admin()->id);
        $dir = $this->extractBackup($ledger->file_path);

        try {
            $this->assertFileExists("{$dir}/storage_files/secure_vault_legacy/{$this->marker}/legacy.enc");
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function test_restore_brings_vault_files_back(): void
    {
        $content = 'RESTORE-ME-' . Str::random(32);
        $relative = $this->putVaultFile($content);

        $ledger = EnterpriseBackupService::createBackup('manual', $this->admin()->id);
        Storage::disk('local')->delete($relative);
        $this->assertFalse(Storage::disk('local')->exists($relative));

        $result = EnterpriseBackupService::restoreBackup($ledger->id);

        $this->assertTrue(Storage::disk('local')->exists($relative), 'Vault file not restored');
        $this->assertSame($content, Storage::disk('local')->get($relative));
        $this->assertGreaterThanOrEqual(1, $result['restored_vault_files'] ?? 0);
    }

    public function test_enterprise_backup_command_archives_real_vault(): void
    {
        $relative = $this->putVaultFile('CMD-' . Str::random(16));
        $vaultRelative = substr($relative, strlen('secure_vault/'));

        $exit = Artisan::call('backup:enterprise-snapshot');
        $this->assertSame(0, $exit, Artisan::output());

        $dirs = glob(storage_path('app/backups/snapshot_*'), GLOB_ONLYDIR) ?: [];
        $this->assertNotEmpty($dirs, 'Command produced no backup directory');
        usort($dirs, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $latest = $dirs[0];

        try {
            $this->assertFileExists("{$latest}/secure_vault/{$vaultRelative}");
        } finally {
            File::deleteDirectory($latest);
        }
    }
}
