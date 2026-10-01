<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToBranch;

class GradeBatch extends Model
{
    use HasFactory, BelongsToBranch;

    protected $fillable = [
        'batch_code',
        'branch_id',
        'academic_year_id',
        'study_year_id',
        'department_id',
        'semester',
        'exam_type',
        'status',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'rejection_notes',
        'batch_digital_hash',
    ];

    protected $casts = [
        'semester' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function studyYear(): BelongsTo
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function studentGrades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['SUBMITTED_TO_HQ', 'HQ_APPROVED', 'FINAL_ARCHIVED']);
    }
}
