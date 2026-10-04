<?php

namespace App\Jobs;

use App\Models\SystemAuditTrail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessOfflineAuditSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array<int> List of staged audit trail IDs or empty for all pending
     */
    public array $stagedIds;

    /**
     * Create a new job instance.
     */
    public function __construct(array $stagedIds = [])
    {
        $this->stagedIds = $stagedIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            // Retrieve staged records ordered by client timestamp (staged_at)
            $query = SystemAuditTrail::withoutGlobalScopes()
                ->where('is_offline_staged', true);

            if (!empty($this->stagedIds)) {
                $query->whereIn('id', $this->stagedIds);
            }

            $stagedRecords = $query->orderBy('staged_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($stagedRecords->isEmpty()) {
                return;
            }

            foreach ($stagedRecords as $record) {
                // Find latest canonical record under lock to maintain strict sequential chain
                $latestCanonical = SystemAuditTrail::withoutGlobalScopes()
                    ->where('is_offline_staged', false)
                    ->whereNotNull('record_hash')
                    ->where('id', '!=', $record->id)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                $prevHash = $latestCanonical?->canonical_ledger_hash 
                    ?? $latestCanonical?->record_hash 
                    ?? str_repeat('0', 64);

                $canonicalTime = Carbon::now();

                $payloadString = is_array($record->payload) 
                    ? json_encode($record->payload, JSON_UNESCAPED_UNICODE) 
                    : (string) $record->payload;

                $hashMaterial = implode('|', [
                    $prevHash,
                    $record->event_type,
                    (string) ($record->user_id ?? 0),
                    (string) ($record->branch_id ?? 0),
                    $record->description,
                    $record->ip_address,
                    $payloadString,
                    $canonicalTime->format('Y-m-d H:i:s'),
                ]);

                $canonicalHash = hash('sha256', $hashMaterial);

                // Atomically update into canonical chain
                DB::table('system_audit_trails')
                    ->where('id', $record->id)
                    ->update([
                        'previous_hash'         => $prevHash,
                        'record_hash'           => $canonicalHash,
                        'canonical_ledger_hash' => $canonicalHash,
                        'is_offline_staged'     => false,
                        'created_at'            => $canonicalTime,
                    ]);
            }

            Log::info("ProcessOfflineAuditSyncJob: successfully inserted {$stagedRecords->count()} offline audit records into canonical ledger.");
        });
    }
}
