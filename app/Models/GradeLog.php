<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToBranch;
use Exception;

class GradeLog extends Model
{
    use HasFactory, BelongsToBranch;

    public $timestamps = false;

    protected $fillable = [
        'student_grade_id',
        'student_id',
        'course_id',
        'branch_id',
        'modified_field',
        'old_value',
        'new_value',
        'user_id',
        'ip_address',
        'user_agent',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Enforce strict append-only immutability. No updates or deletes allowed!
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function () {
            throw new Exception('لا يمكن تعديل سجل التدقيق الجنائي للدرجات (Immutable Audit Log).');
        });

        static::deleting(function () {
            throw new Exception('لا يمكن حذف سجل التدقيق الجنائي للدرجات (Immutable Audit Log).');
        });
    }

    public function studentGrade(): BelongsTo
    {
        return $this->belongsTo(StudentGrade::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
