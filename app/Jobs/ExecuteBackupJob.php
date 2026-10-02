<?php

namespace App\Jobs;

use App\Services\EnterpriseBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes timeout for large datasets

    protected string $type;
    protected ?int $userId;
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(string $type = 'manual', ?int $userId = null, array $options = [])
    {
        $this->type = $type;
        $this->userId = $userId;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting background Enterprise Backup Job (Type: {$this->type}, User: {$this->userId})");
        EnterpriseBackupService::createBackup($this->type, $this->userId, $this->options);
    }
}
