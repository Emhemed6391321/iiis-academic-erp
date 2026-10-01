<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'from_branch_id',
        'to_branch_id',
        'status',
        'reason',
        'requested_by',
        'approved_by',
        'approved_at',
        'central_affairs_statement',
        'central_affairs_approved_by',
        'central_affairs_approved_at',
        'receiving_branch_status',
        'receiving_branch_decision_notes',
        'receiving_branch_decided_by',
        'receiving_branch_decided_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'central_affairs_approved_at' => 'datetime',
        'receiving_branch_decided_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
