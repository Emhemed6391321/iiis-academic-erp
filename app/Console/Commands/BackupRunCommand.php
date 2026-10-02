<?php

namespace App\Console\Commands;

use App\Services\EnterpriseBackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iiis:backup-run 
                            {--type=scheduled : Type of backup: scheduled, manual, or emergency}
                            {--user= : User ID initiating the backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute automated encrypted enterprise backup with SHA-256 integrity seal';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('================================================================');
        $this->info('  IIIS Academic ERP — Enterprise Backup & Disaster Recovery    ');
        $this->info('  المعهد التخصصي للدراسات الإسلامية — منظومة النسخ والتعافي الذاتي   ');
        $this->info('================================================================');

        $type = $this->option('type') ?: 'scheduled';
        $userId = $this->option('user') ? (int)$this->option('user') : null;

        $this->line("⏳ Initiating [{$type}] encrypted backup...");

        try {
            $ledger = EnterpriseBackupService::createBackup($type, $userId);

            $this->newLine();
            $this->info('================================================================');
            $this->info('  ✓ SUCCESS: Enterprise Backup Created & Verified!             ');
            $this->info('================================================================');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Backup ID', $ledger->id],
                    ['File Name', $ledger->file_name],
                    ['Size', $ledger->human_readable_size . " ({$ledger->file_size_bytes} bytes)"],
                    ['Algorithm', $ledger->encryption_algorithm],
                    ['SHA-256 Seal', $ledger->sha256_checksum],
                    ['Database Driver', $ledger->database_driver],
                    ['Included Tables', $ledger->included_tables_count],
                    ['Archived Files', $ledger->included_files_count],
                    ['Status', $ledger->status],
                    ['Created At', $ledger->created_at->toDateTimeString()],
                ]
            );

            return 0;

        } catch (Throwable $e) {
            $this->error('CRITICAL: Backup process failed with error: ' . $e->getMessage());
            return 1;
        }
    }
}
