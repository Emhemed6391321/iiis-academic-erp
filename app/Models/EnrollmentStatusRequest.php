<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentStatusRequest extends Model
{
    protected $fillable = [
        'student_id', 'request_type', 'target_academic_year_id',
        'reason', 'document_path',
        'branch_status', 'hq_status', 'final_status',
        'rejection_notes', 'created_by',
        'branch_reviewed_by', 'hq_reviewed_by',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function targetYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'target_academic_year_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabel(): string
    {
        return match($this->request_type) {
            'PAUSE'   => 'إيقاف قيد',
            'RENEWAL' => 'تجديد قيد',
            default   => $this->request_type,
        };
    }
}
