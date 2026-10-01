<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToBranch;
use Carbon\Carbon;

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
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // 1. Calculate tamper-evident hash chain on record creation
        static::creating(function (self $model) {
            if (empty($model->created_at)) {
                $model->created_at = Carbon::now();
            }

            // Retrieve previous record hash in chain
            $latestRecord = static::withoutGlobalScopes()->orderByDesc('id')->first();
            $prevHash = $latestRecord?->record_hash ?? str_repeat('0', 64);

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

            $model->record_hash = hash('sha256', $hashMaterial);
        });

        // 2. Strict append-only enforcement: Prevent updates
        static::updating(function () {
            throw new \RuntimeException('SECURITY VIOLATION: System audit trails are immutable and cannot be updated.');
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
     * Verify the entire cryptographic hash chain for integrity.
     * 
     * @return array{intact: bool, broken_id: int|null, count: int}
     */
    public static function verifyChainIntegrity(): array
    {
        $records = static::withoutGlobalScopes()->orderBy('id')->get();
        $expectedPrev = str_repeat('0', 64);
        $count = 0;

        foreach ($records as $record) {
            $count++;

            // If the record was created before hash chain, skip or initialize
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
