<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyTypeChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'old_type',
        'new_type',
        'reason',
        'is_full_absence',
        'document_path',
        'branch_status',
        'hq_status',
        'board_status',
        'final_status',
        'created_by',
    ];

    protected $casts = [
        'is_full_absence' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getOldTypeLabelAttribute(): string
    {
        return match($this->old_type) {
            'REGULAR' => 'نظامي',
            'INTISAB' => 'انتساب',
            default => $this->old_type,
        };
    }

    public function getNewTypeLabelAttribute(): string
    {
        return match($this->new_type) {
            'REGULAR' => 'نظامي',
            'INTISAB' => 'انتساب',
            default => $this->new_type,
        };
    }
}
