<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToBranch;

class AttendanceWarningNotice extends Model
{
    use BelongsToBranch;

    protected $table = 'attendance_warning_notices';

    protected $fillable = [
        'student_id',
        'branch_id',
        'academic_year_id',
        'notice_number',
        'warning_level',
        'unexcused_days_count',
        'total_absence_days',
        'absence_percentage',
        'notice_date',
        'admin_statement',
        'delivery_status',
        'guardian_contacted_at',
        'guardian_response',
        'issued_by',
    ];

    protected $casts = [
        'notice_date'            => 'date',
        'guardian_contacted_at'  => 'date',
        'unexcused_days_count'   => 'integer',
        'total_absence_days'     => 'integer',
        'absence_percentage'     => 'decimal:2',
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

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function getWarningLevelLabelAttribute(): string
    {
        return match($this->warning_level) {
            'FIRST_WARNING'     => 'إنذار غياب أول (3 أيام)',
            'SECOND_WARNING'    => 'إنذار غياب ثانٍ (5 أيام)',
            'FINAL_WARNING'     => 'إنذار غياب نهائي (10 أيام / خطر الحرمان)',
            'EXPULSION_NOTICE'  => 'قرار شطب / حرمان بسبب تجاوز نصاب الغياب',
            default             => $this->warning_level,
        };
    }
}
