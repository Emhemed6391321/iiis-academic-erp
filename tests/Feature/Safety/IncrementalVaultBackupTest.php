<?php

namespace Tests\Feature\Safety;

use App\Models\Role;
use App\Models\User;
use App\Services\EnterpriseBackupService;
use App\Support\SecureVaultPaths;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

/**
 * Hotfix 2.5.11: vault files are immutable, so nightly backups must not
 * re-archive them (2.3GB/night would fill the 39GB disk). Vault files are
 * mirrored once into a write-only mirror; each backup carries a manifest of
 * paths + sha256 and restore rebuilds the vault from the mirror.
 */
class IncrementalVaultBackupTest extends TestCase
{
    use RefreshDatabase;

    private string $marker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        $this->marker = '__inc_test_' . Str::random(8);
    }

    protected function tearDown(): void
    {
        foreach ([SecureVaultPaths::primary(), SecureVaultPaths::legacy()] as $root) {
            File::deleteDirectory("{$root}/{$this->marker}");
        }
        foreach (['primary', 'legacy'] as $r) {
            File::deleteDirectory(SecureVaultPaths::mirror() . "/{$r}/{$this->marker}");
        }
        parent::tearDown();
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام الرئيسي', 'is_global' => true]);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function putVaultFile(string $content): string
    {
        $relative = "secure_vault/{$this->marker}/branch_1/" . Str::uuid() . '.pdf.enc';
        Storage::disk('local')->put($relative, $content);
        return substr($relative, strlen('secure_vault/'));
    }

    private function extract(string $encryptedRelativePath): string
    {
        $dir = storage_path('app/backups/inc_extract_' . uniqid());
        File::makeDirectory($dir, 0750, true);
        EnterpriseBackupService::decryptFile(storage_path('app/' . $encryptedRelativePath), "{$dir}/payload.zip");
        $zip = new ZipArchive();
        $this->assertTrue($zip->open("{$dir}/payload.zip") === true);
        $zip->extractTo($dir);
        $zip->close();
        return $dir;
    }

    public function test_nightly_backup_carries_manifest_not_vault_contents_and_mirrors_files(): void
    {
        $content = 'VAULT-' . Str::random(64);
        $rel = $this->putVaultFile($content);

        $ledger = EnterpriseBackupService::createBackup('scheduled', $this->admin()->id);
        $dir = $this->extract($ledger->file_path);

        try {
            $this->assertFileDoesNotExist("{$dir}/storage_files/secure_vault/{$rel}", 'Vault contents must not be re-archived nightly');
            $manifest = json_decode(File::get("{$dir}/vault_manifest.json"), true);
            $entry = collect($manifest['files'])->firstWhere('path', $rel);
            $this->assertNotNull($entry, 'Vault file missing from manifest');
            $this->assertSame('primary', $entry['root']);
            $this->assertSame(hash('sha256', $content), $entry['sha256']);
            $this->assertSame($content, File::get(SecureVaultPaths::mirror() . "/primary/{$rel}"));
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function test_second_backup_does_not_recopy_unchanged_files(): void
    {
        $rel = $this->putVaultFile('STABLE-' . Str::random(32));
        EnterpriseBackupService::createBackup('scheduled', $this->admin()->id);

        $mirrorFile = SecureVaultPaths::mirror() . "/primary/{$rel}";
        touch($mirrorFile, time() - 3600);
        clearstatcache();
        $mtime = filemtime($mirrorFile);

        $second = EnterpriseBackupService::createBackup('scheduled', $this->admin()->id);
        clearstatcache();

        $this->assertSame($mtime, filemtime($mirrorFile), 'Unchanged vault file was copied again');
        $this->assertSame(0, $second->manifest_metadata['storage_files']['vault_mirror']['copied_files'] ?? null);
    }

    public function test_restore_rebuilds_vault_from_mirror_and_verifies_hash(): void
    {
        $content = 'RESTORE-' . Str::random(40);
        $rel = $this->putVaultFile($content);
        $ledger = EnterpriseBackupService::createBackup('scheduled', $this->admin()->id);

        Storage::disk('local')->delete("secure_vault/{$rel}");
        $result = EnterpriseBackupService::restoreBackup($ledger->id);

        $this->assertSame($content, Storage::disk('local')->get("secure_vault/{$rel}"));
        $this->assertGreaterThanOrEqual(1, $result['restored_vault_files']);
        $this->assertSame(0, $result['missing_vault_files']);
    }

    public function test_restore_reports_files_missing_or_corrupt_in_mirror(): void
    {
        $rel = $this->putVaultFile('WILL-BE-LOST');
        $ledger = EnterpriseBackupService::createBackup('scheduled', $this->admin()->id);

        Storage::disk('local')->delete("secure_vault/{$rel}");
        File::put(SecureVaultPaths::mirror() . "/primary/{$rel}", 'CORRUPTED');

        $result = EnterpriseBackupService::restoreBackup($ledger->id);

        $this->assertSame(1, $result['missing_vault_files']);
        $this->assertFalse(Storage::disk('local')->exists("secure_vault/{$rel}"), 'Corrupt mirror copy must not be restored');
    }

    public function test_full_option_produces_self_contained_archive(): void
    {
        $rel = $this->putVaultFile('FULL-' . Str::random(16));
        $ledger = EnterpriseBackupService::createBackup('manual', $this->admin()->id, ['include_vault_files' => true]);
        $dir = $this->extract($ledger->file_path);

        try {
            $this->assertFileExists("{$dir}/storage_files/secure_vault/{$rel}");
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function test_mirror_survives_retention_and_snapshot_cleanup(): void
    {
        $rel = $this->putVaultFile('KEEP-' . Str::random(16));
        EnterpriseBackupService::createBackup('scheduled', $this->admin()->id);

        $old = time() - 30 * 86400;
        touch(SecureVaultPaths::mirror(), $old);
        $encryptedDir = storage_path('app/backups/encrypted');
        touch($encryptedDir, $old);

        Artisan::call('backup:enterprise-snapshot');

        $this->assertFileExists(SecureVaultPaths::mirror() . "/primary/{$rel}", 'Snapshot cleanup deleted the vault mirror');
        $this->assertDirectoryExists($encryptedDir, 'Snapshot cleanup deleted the encrypted backups directory');

        foreach (glob(storage_path('app/backups/snapshot_*'), GLOB_ONLYDIR) ?: [] as $d) {
            File::deleteDirectory($d);
        }
    }
}
