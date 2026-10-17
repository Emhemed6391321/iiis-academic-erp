<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdministrativeRequest extends Model
{
    use HasFactory;

    public const TYPE_AGE_EXCEPTION = 'AGE_EXCEPTION';
    public const TYPE_GRADE_LEVEL = 'GRADE_LEVEL_ENROLLMENT';

    public const STATUS_PENDING = 'PENDING_HQ';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'request_type',
        'status',
        'branch_id',
        'requested_by',
        'subject_name',
        'national_id',
        'passport_number',
        'administrative_number',
        'study_year_id',
        'justification',
        'decided_by',
        'decided_at',
        'decision_notes',
        'consumed_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function decision(): HasOne
    {
        return $this->hasOne(AdministrativeDecision::class, 'request_id');
    }
}
