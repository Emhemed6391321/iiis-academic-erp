<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToBranch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SystemAuditTrail extends Model
{
    use HasFactory, BelongsToBranch;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'branch_id',
        'event_type',
        'description',
        'ip_address',
        'payload',
        'previous_hash',
        'record_hash',
        'staging_offline_hash',
        'canonical_ledger_hash',
        'is_offline_staged',
        'staged_at',
        'client_uuid',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_offline_staged' => 'boolean',
        'staged_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // 1. Calculate tamper-evident hash chain on record creation
        static::creating(function (self $model) {
            if (empty($model->created_at)) {
                $model->created_at = Carbon::now();
            }

            // Staging offline record logic:
            if ($model->is_offline_staged) {
                if (empty($model->staged_at)) {
                    $model->staged_at = $model->created_at;
                }
                $payloadString = is_array($model->payload) 
                    ? json_encode($model->payload, JSON_UNESCAPED_UNICODE) 
                    : (string) $model->payload;

                $stagingMaterial = implode('|', [
                    'STAGING_OFFLINE',
                    (string) ($model->client_uuid ?? ''),
                    $model->event_type,
                    (string) ($model->user_id ?? 0),
                    (string) ($model->branch_id ?? 0),
                    $model->description,
                    $model->ip_address,
                    $payloadString,
                    $model->staged_at->format('Y-m-d H:i:s'),
                ]);

                $model->staging_offline_hash = hash('sha256', $stagingMaterial);
                $model->previous_hash = null;
                $model->record_hash = null;
                $model->canonical_ledger_hash = null;
                return;
            }

            // Canonical Ledger Logic:
            $latestCanonical = static::withoutGlobalScopes()
                ->where('is_offline_staged', false)
                ->whereNotNull('record_hash')
                ->orderByDesc('id')
                ->first();

            $prevHash = $latestCanonical?->canonical_ledger_hash ?? $latestCanonical?->record_hash ?? str_repeat('0', 64);
            $model->previous_hash = $prevHash;

            $payloadString = is_array($model->payload) 
                ? json_encode($model->payload, JSON_UNESCAPED_UNICODE) 
                : (string) $model->payload;

            $hashMaterial = implode('|', [
                $prevHash,
                $model->event_type,
                (string) ($model->user_id ?? 0),
                (string) ($model->branch_id ?? 0),
                $model->description,
                $model->ip_address,
                $payloadString,
                $model->created_at->format('Y-m-d H:i:s'),
            ]);

            $canonicalHash = hash('sha256', $hashMaterial);
            $model->record_hash = $canonicalHash;
            $model->canonical_ledger_hash = $canonicalHash;
        });

        // 2. Append-only enforcement: Prevent updates (except for promoting staged records to canonical chain)
        static::updating(function (self $model) {
            $dirty = $model->getDirty();
            $allowedPromotionKeys = ['canonical_ledger_hash', 'record_hash', 'previous_hash', 'is_offline_staged', 'created_at'];
            $otherChanges = array_diff(array_keys($dirty), $allowedPromotionKeys);

            if (!empty($otherChanges)) {
                throw new \RuntimeException('SECURITY VIOLATION: System audit trails are immutable and cannot be updated.');
            }
        });

        // 3. Strict append-only enforcement: Prevent deletions
        static::deleting(function () {
            throw new \RuntimeException('SECURITY VIOLATION: System audit trails are append-only and cannot be deleted.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $eventType,
        string $description,
        array $payload = [],
        ?int $userId = null,
        ?int $branchId = null,
        ?string $modelType = null,
        ?int $modelId = null,
        array $oldValues = [],
        array $newValues = [],
        string $severity = 'INFO',
        $request = null
    ): ?self {
        try {
            $extraPayload = array_filter([
                'model_type' => $modelType,
                'model_id'   => $modelId,
                'old_values' => !empty($oldValues) ? $oldValues : null,
                'new_values' => !empty($newValues) ? $newValues : null,
                'severity'   => $severity,
            ]);

            $finalPayload = array_merge($payload, $extraPayload);
            $req = $request instanceof \Illuminate\Http\Request ? $request : request();

            return static::create([
                'user_id'     => $userId ?? auth()->id(),
                'branch_id'   => $branchId ?? (auth()->check() ? auth()->user()->branch_id : null),
                'event_type'  => $eventType,
                'description' => $description,
                'ip_address'  => $req ? $req->ip() : '127.0.0.1',
                'payload'     => $finalPayload,
                'created_at'  => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Stage an offline audit trail record without breaking canonical hash chain.
     */
    public static function stageOffline(
        string $eventType,
        string $description,
        array $payload = [],
        string $clientUuid = '',
        ?Carbon $clientRecordedAt = null,
        ?int $userId = null,
        ?int $branchId = null
    ): self {
        return static::create([
            'user_id'           => $userId ?? auth()->id(),
            'branch_id'         => $branchId ?? (auth()->check() ? auth()->user()->branch_id : null),
            'event_type'        => $eventType,
            'description'       => $description,
            'ip_address'        => request() ? request()->ip() : '127.0.0.1',
            'payload'           => $payload,
            'client_uuid'       => $clientUuid,
            'is_offline_staged' => true,
            'staged_at'         => $clientRecordedAt ?? Carbon::now(),
            'created_at'        => Carbon::now(),
        ]);
    }

    /**
     * Atomically promote a staged record into the canonical ledger.
     */
    public function promoteToCanonical(): void
    {
        if (!$this->is_offline_staged) {
            return;
        }

        DB::transaction(function () {
            // Find latest canonical record under lock
            $latestCanonical = static::withoutGlobalScopes()
                ->where('is_offline_staged', false)
                ->whereNotNull('record_hash')
                ->where('id', '!=', $this->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $prevHash = $latestCanonical?->canonical_ledger_hash ?? $latestCanonical?->record_hash ?? str_repeat('0', 64);
            $canonicalCreatedAt = Carbon::now();

            $payloadString = is_array($this->payload) 
                ? json_encode($this->payload, JSON_UNESCAPED_UNICODE) 
                : (string) $this->payload;

            $hashMaterial = implode('|', [
                $prevHash,
                $this->event_type,
                (string) ($this->user_id ?? 0),
                (string) ($this->branch_id ?? 0),
                $this->description,
                $this->ip_address,
                $payloadString,
                $canonicalCreatedAt->format('Y-m-d H:i:s'),
            ]);

            $canonicalHash = hash('sha256', $hashMaterial);

            DB::table('system_audit_trails')
                ->where('id', $this->id)
                ->update([
                    'previous_hash'         => $prevHash,
                    'record_hash'           => $canonicalHash,
                    'canonical_ledger_hash' => $canonicalHash,
                    'is_offline_staged'     => false,
                    'created_at'            => $canonicalCreatedAt,
                ]);

            $this->previous_hash = $prevHash;
            $this->record_hash = $canonicalHash;
            $this->canonical_ledger_hash = $canonicalHash;
            $this->is_offline_staged = false;
            $this->created_at = $canonicalCreatedAt;
        });
    }

    /**
     * Verify the entire cryptographic hash chain for integrity.
     * 
     * @return array{intact: bool, broken_id: int|null, count: int}
     */
    public static function verifyChainIntegrity(): array
    {
        $records = static::withoutGlobalScopes()
            ->where('is_offline_staged', false)
            ->whereNotNull('record_hash')
            ->orderBy('id')
            ->get();

        $expectedPrev = str_repeat('0', 64);
        $count = 0;

        foreach ($records as $record) {
            $count++;

            if (empty($record->record_hash)) {
                continue;
            }

            if ($record->previous_hash !== $expectedPrev) {
                return ['intact' => false, 'broken_id' => $record->id, 'count' => $count];
            }

            $payloadString = is_array($record->payload) 
                ? json_encode($record->payload, JSON_UNESCAPED_UNICODE) 
                : (string) $record->payload;

            $hashMaterial = implode('|', [
                $expectedPrev,
                $record->event_type,
                (string) ($record->user_id ?? 0),
                (string) ($record->branch_id ?? 0),
                $record->description,
                $record->ip_address,
                $payloadString,
                $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : '',
            ]);

            $computed = hash('sha256', $hashMaterial);
            if (!hash_equals($record->record_hash, $computed)) {
                return ['intact' => false, 'broken_id' => $record->id, 'count' => $count];
            }

            $expectedPrev = $record->record_hash;
        }

        return ['intact' => true, 'broken_id' => null, 'count' => $count];
    }
}
