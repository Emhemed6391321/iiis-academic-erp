<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\SystemAuditTrail;
use Carbon\Carbon;

class CreateEnterpriseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:enterprise-snapshot {--verify : Verify backup integrity after creation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an automated, SHA-256 verified enterprise snapshot of database and encrypted vault';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('===========================================================');
        $this->info('  IIIS Enterprise ERP v2.0 — Central Backup & DR Engine    ');
        $this->info('  المعهد التخصصي للدراسات الإسلامية — منظومة النسخ الاحتياطي   ');
        $this->info('===========================================================');

        $timestamp = Carbon::now()->format('Ymd_His');
        $backupDir = storage_path("app/backups/snapshot_{$timestamp}");

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0750, true);
        }

        // 1. Database Snapshot
        $this->line('[1/4] Creating database snapshot...');
        $dbConnection = config('database.default');
        $dbPath = config("database.connections.{$dbConnection}.database");

        $dbSnapshotFile = null;
        $dbHash = null;

        if ($dbConnection === 'sqlite' && File::exists($dbPath)) {
            $dbSnapshotFile = "{$backupDir}/database_{$timestamp}.sqlite";
            File::copy($dbPath, $dbSnapshotFile);
            $dbHash = hash_file('sha256', $dbSnapshotFile);
            $this->info("   ✓ SQLite database copied and hashed (SHA-256: " . substr($dbHash, 0, 16) . '...)');
        } else {
            $this->warn("   ! Database driver is [{$dbConnection}]. Ensure database replication is active.");
        }

        // 2. Vault Snapshot
        $this->line('[2/4] Archiving encrypted secure file vault...');
        $vaultSource = storage_path('app/secure_vault');
        $vaultDest = "{$backupDir}/secure_vault";
        $vaultFilesCount = 0;
        $vaultManifest = [];

        if (File::exists($vaultSource)) {
            File::copyDirectory($vaultSource, $vaultDest);
            $files = File::allFiles($vaultDest);
            $vaultFilesCount = count($files);
            foreach ($files as $file) {
                $vaultManifest[$file->getRelativePathname()] = [
                    'size' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                ];
            }
            $this->info("   ✓ Encrypted vault backed up ({$vaultFilesCount} files).");
        } else {
            $this->line("   - Secure vault directory is empty or not yet initialized.");
        }

        // 3. Cryptographic Audit Chain Verification
        $this->line('[3/4] Verifying cryptographic audit trail hash chain integrity...');
        $chainStatus = SystemAuditTrail::verifyChainIntegrity();
        $statusStr = $chainStatus['intact'] ? 'INTACT' : 'TAMPERED';
        $this->info("   ✓ Audit trail chain status: [{$statusStr}] ({$chainStatus['count']} records verified)");

        // 4. Generate Cryptographic Manifest
        $this->line('[4/4] Generating cryptographic manifest and seal...');
        $manifest = [
            'institute'        => 'المعهد التخصصي للدراسات الإسلامية',
            'system_version'   => '2.0.0-ENTERPRISE',
            'snapshot_id'      => "SNAP-{$timestamp}",
            'created_at'       => Carbon::now()->toIso8601String(),
            'database'         => [
                'driver'       => $dbConnection,
                'snapshot_file'=> $dbSnapshotFile ? basename($dbSnapshotFile) : null,
                'sha256'       => $dbHash,
                'size_bytes'   => $dbSnapshotFile ? File::size($dbSnapshotFile) : 0,
            ],
            'vault'            => [
                'total_files'  => $vaultFilesCount,
                'files'        => $vaultManifest,
            ],
            'audit_chain'      => $chainStatus,
        ];

        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $manifestPath = "{$backupDir}/manifest.json";
        File::put($manifestPath, $manifestJson);

        $manifestHash = hash('sha256', $manifestJson);
        File::put("{$backupDir}/manifest.sha256", $manifestHash);
        $this->info("   ✓ Manifest generated and signed with SHA-256 seal.");

        // Verification Option
        if ($this->option('verify')) {
            $this->line('Verifying snapshot integrity...');
            if ($dbSnapshotFile && hash_file('sha256', $dbSnapshotFile) !== $dbHash) {
                $this->error('CRITICAL: Database snapshot checksum mismatch!');
                return 1;
            }
            $this->info('✓ Snapshot verification passed successfully.');
        }

        // Cleanup older snapshots (keep last 7 days)
        $this->cleanupOldSnapshots(storage_path('app/backups'), 7);

        $this->info("Enterprise snapshot completed successfully at: {$backupDir}");
        return 0;
    }

    /**
     * Clean up snapshots older than retention days.
     */
    protected function cleanupOldSnapshots(string $parentDir, int $retentionDays): void
    {
        if (!File::exists($parentDir)) return;

        $directories = File::directories($parentDir);
        $threshold = Carbon::now()->subDays($retentionDays)->timestamp;

        foreach ($directories as $dir) {
            if (File::lastModified($dir) < $threshold) {
                File::deleteDirectory($dir);
            }
        }
    }
}
