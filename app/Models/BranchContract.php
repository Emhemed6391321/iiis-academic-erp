<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToBranch;

class BranchContract extends Model
{
    use HasFactory, BelongsToBranch;

    protected $fillable = [
        'contract_number',
        'internal_number',
        'branch_id',
        'property_id',
        'contract_type',
        'title',
        'contractor_name',
        'contractor_phone',
        'lessor_name',
        'lessee_name',
        'contract_date',
        'total_value',
        'paid_value',
        'rent_amount',
        'payment_frequency',
        'installment_amount',
        'deposit_amount',
        'payment_method',
        'due_day',
        'annual_increase_percentage',
        'grace_period_days',
        'renewal_terms',
        'start_date',
        'end_date',
        'duration_months',
        'progress_percentage',
        'status',
        'document_path',
        'notes',
        'created_by_id',
        'approved_by_id',
        'approved_at',
        'suspended_at',
        'suspension_reason',
        'suspension_document',
        'suspended_by_id',
        'terminated_at',
        'termination_reason',
        'terminated_by_id',
        'remaining_obligations',
        'property_status_after_termination',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'contract_date' => 'date',
        'approved_at' => 'datetime',
        'suspended_at' => 'datetime',
        'terminated_at' => 'datetime',
        'total_value' => 'decimal:2',
        'paid_value' => 'decimal:2',
        'rent_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'annual_increase_percentage' => 'decimal:2',
        'remaining_obligations' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(ContractInstallment::class, 'contract_id')->orderBy('due_date', 'asc');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by_id');
    }

    public function terminatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terminated_by_id');
    }
}
