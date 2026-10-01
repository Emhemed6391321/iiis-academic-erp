<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcuseRequest extends Model
{
    protected $fillable = [
        'student_id', 'start_date', 'end_date', 'reason',
        'attachment_path', 'status', 'submitted_by',
        'reviewed_by', 'review_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'PENDING'  => 'قيد المراجعة',
            'APPROVED' => 'مقبول',
            'REJECTED' => 'مرفوض',
            default    => $this->status,
        };
    }
}
