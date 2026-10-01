<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToBranch;

class BranchRequest extends Model
{
    use HasFactory, BelongsToBranch;

    protected $fillable = [
        'ticket_number',
        'branch_id',
        'created_by',
        'category',
        'priority',
        'title',
        'description',
        'status',
        'assigned_to',
        'target_date',
        'completed_at',
        'estimated_cost',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function trackings(): HasMany
    {
        return $this->hasMany(BranchRequestTracking::class, 'request_id');
    }
}
