<?php

namespace Tests\Feature\Safety;

use App\Support\SecureVaultPaths;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Hotfix 2.5.9: the health check must probe the real vault directory
 * (local disk / secure_vault), not the legacy storage/app/secure_vault.
 */
class SystemHealthVaultPathTest extends TestCase
{
    public function test_health_controller_probes_real_vault_path(): void
    {
        $source = File::get(app_path('Http/Controllers/Api/SystemHealthController.php'));

        $this->assertStringNotContainsString("storage_path('app/secure_vault')", $source);
        $this->assertStringContainsString('SecureVaultPaths::primary()', $source);
    }

    public function test_all_backup_call_sites_use_shared_vault_paths(): void
    {
        foreach ([
            'Services/EnterpriseBackupService.php',
            'Console/Commands/CreateEnterpriseBackup.php',
            'Http/Controllers/Api/SystemHealthController.php',
        ] as $file) {
            $source = File::get(app_path($file));
            $this->assertStringNotContainsString("storage_path('app/secure_vault')", $source, "{$file} still hard-codes the legacy vault path");
        }

        $this->assertStringEndsWith('secure_vault', str_replace('\\', '/', SecureVaultPaths::primary()));
        $this->assertStringContainsString('private', str_replace('\\', '/', SecureVaultPaths::primary()));
        $this->assertSame(
            str_replace('\\', '/', storage_path('app/secure_vault')),
            str_replace('\\', '/', SecureVaultPaths::legacy())
        );
    }
}
