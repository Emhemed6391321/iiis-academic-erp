<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_uuid',
        'document_type',
        'student_id',
        'branch_id',
        'academic_year_id',
        'hash_signature',
        'metadata_payload',
        'signatory_name',
        'signatory_position',
        'verification_url',
        'qr_payload',
        'issue_date',
        'expiry_date',
        'status',
        'revoked_at',
        'revoked_by_id',
        'revocation_reason',
    ];

    protected $casts = [
        'metadata_payload' => 'array',
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'revoked_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_id');
    }

    public function isValid(): bool
    {
        if ($this->status !== 'VALID') {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }

        return true;
    }
}
