<?php

namespace App\Console\Commands;

use App\Models\BackupLedger;
use App\Services\EnterpriseBackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iiis:backup-restore 
                            {id : The ID or filename of the backup to restore}
                            {--force : Force restore without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore database and media files from an encrypted enterprise backup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('================================================================');
        $this->warn('  ⚠️ IIIS Enterprise Disaster Recovery & Restoration Engine    ');
        $this->warn('================================================================');

        $id = $this->argument('id');

        $ledger = is_numeric($id)
            ? BackupLedger::find($id)
            : BackupLedger::where('file_name', $id)->first();

        if (!$ledger) {
            $this->error("Backup with ID or File [{$id}] was not found in the backups ledger.");
            return 1;
        }

        $this->table(
            ['Property', 'Target Backup Information'],
            [
                ['ID', $ledger->id],
                ['File Name', $ledger->file_name],
                ['Size', $ledger->human_readable_size],
                ['Created At', $ledger->created_at->toDateTimeString()],
                ['Checksum', $ledger->sha256_checksum],
            ]
        );

        if (!$this->option('force')) {
            if (!$this->confirm('WARNING: Restoring will overwrite current database state and media files. Do you want to proceed with restore?')) {
                $this->info('Restore aborted by user.');
                return 0;
            }
        }

        $this->line('⏳ Verifying checksum, decrypting, and applying state...');

        try {
            $result = EnterpriseBackupService::restoreBackup($ledger->id);

            $this->newLine();
            $this->info('================================================================');
            $this->info('  ✓ SUCCESS: Full System State Restored Successfully!           ');
            $this->info('================================================================');
            $this->line("Database Restored: " . ($result['restored_database'] ? 'YES' : 'SKIPPED'));
            $this->line("Files Restored: {$result['restored_files_count']} files");

            return 0;

        } catch (Throwable $e) {
            $this->error('CRITICAL: Restore failed: ' . $e->getMessage());
            return 1;
        }
    }
}
