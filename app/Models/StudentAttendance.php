<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToBranch;

class StudentAttendance extends Model
{
    use BelongsToBranch;

    protected $table = 'student_attendance';

    protected $fillable = [
        'student_id',
        'branch_id',
        'academic_year_id',
        'study_year_id',
        'department_id',
        'record_date',
        'day_of_week',
        'check_in_time',
        'check_out_time',
        'status',
        'late_minutes',
        'departure_status',
        'departure_reason',
        'absence_reason',
        'verification_method',
        'device_id',
        'biometric_log_id',
        'early_permission_slip_number',
        'early_permission_reason',
        'early_permission_guardian_name',
        'early_permission_guardian_phone',
        'early_permission_authorized_by',
        'early_permission_notes',
        'recorded_by',
        'updated_by',
        'modification_reason',
        'sync_nonce',
        'client_recorded_at',
        'is_offline_sync',
    ];

    protected $casts = [
        'record_date'        => 'date',
        'late_minutes'       => 'integer',
        'is_offline_sync'    => 'boolean',
        'client_recorded_at' => 'datetime',
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

    public function studyYear(): BelongsTo
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'PRESENT'          => 'حاضر',
            'ABSENT', 'ABSENT_UNEXCUSED' => 'غائب (بدون عذر)',
            'EXCUSED', 'ABSENT_EXCUSED'  => 'غياب بعذر',
            'LATE'             => 'متأخر',
            'EARLY_DEPARTURE'  => 'انصراف مبكر',
            'OTHER'            => 'حالة خاصة',
            default            => $this->status,
        };
    }

    public function getDepartureStatusLabelAttribute(): string
    {
        return match($this->departure_status) {
            'NOT_DEPARTED'    => 'لم ينصرف بعد',
            'DEPARTED'        => 'انصرف بنهاية الدوام',
            'EARLY_DEPARTURE' => 'انصراف مبكر / بإذن',
            default           => $this->departure_status,
        };
    }

    /**
     * نطاق استعلام لمطابقة التاريخ بدقة مع التوافق التام مع محركات قواعد البيانات (SQLite / MySQL)
     */
    public function scopeForDate($query, $date)
    {
        if (!$date) {
            return $query;
        }

        try {
            $formatted = \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            $formatted = (string)$date;
        }

        return $query->where(function ($q) use ($formatted) {
            $q->whereDate('record_date', $formatted)
              ->orWhere('record_date', 'like', $formatted . '%');
        });
    }
}

