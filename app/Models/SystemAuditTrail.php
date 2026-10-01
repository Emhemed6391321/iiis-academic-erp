<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToBranch;

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
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $eventType, string $description, array $payload = [], ?int $userId = null, ?int $branchId = null): ?self
    {
        try {
            return static::create([
                'user_id'     => $userId ?? auth()->id(),
                'branch_id'   => $branchId ?? (auth()->check() ? auth()->user()->branch_id : null),
                'event_type'  => $eventType,
                'description' => $description,
                'ip_address'  => request()->ip(),
                'payload'     => $payload,
                'created_at'  => \Carbon\Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }
}

