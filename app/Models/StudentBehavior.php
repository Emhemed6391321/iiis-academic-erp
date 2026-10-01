<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBehavior extends Model
{
    protected $fillable = [
        'student_id', 'violation_type', 'warning_level',
        'description', 'action_taken', 'violation_date', 'logged_by',
    ];

    protected $casts = ['violation_date' => 'date'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function getWarningLabelAttribute(): string
    {
        return match($this->warning_level) {
            'LEVEL_1' => 'إنذار أول',
            'LEVEL_2' => 'إنذار ثاني',
            'LEVEL_3' => 'إنذار نهائي',
            default   => $this->warning_level,
        };
    }
}
