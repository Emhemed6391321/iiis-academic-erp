<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'study_year_id',
        'department_id',
        'semester',
        'code',
        'name',
        'credit_hours',
        'weekly_hours',
        'assessment_system',
        'max_coursework_grade',
        'max_midterm_grade',
        'max_final_grade',
        'pass_grade',
        'max_score',
        'pass_min_score',
        'second_round_max',
        'is_active',
    ];

    protected $casts = [
        'semester'             => 'integer',
        'credit_hours'         => 'integer',
        'weekly_hours'         => 'integer',
        'max_coursework_grade' => 'decimal:2',
        'max_midterm_grade'    => 'decimal:2',
        'max_final_grade'      => 'decimal:2',
        'pass_grade'           => 'decimal:2',
        'max_score'            => 'decimal:2',
        'pass_min_score'       => 'decimal:2',
        'second_round_max'     => 'decimal:2',
        'is_active'            => 'boolean',
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

    public function studentGrades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function isSingleHour(): bool
    {
        return $this->weekly_hours === 1;
    }

    public function isSemesterSystem(): bool
    {
        return $this->assessment_system === 'SEMESTER_SYSTEM';
    }

    public function isAnnualPeriodsSystem(): bool
    {
        return $this->assessment_system === 'ANNUAL_PERIODS_SYSTEM';
    }

    public function getTotalMaxGradeAttribute(): float
    {
        return (float) ($this->max_score ?? ($this->max_coursework_grade + $this->max_midterm_grade + $this->max_final_grade));
    }
}
