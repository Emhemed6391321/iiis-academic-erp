<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_batch_id',
        'student_id',
        'course_id',
        'coursework_grade',
        'midterm_grade',
        'final_exam_grade',
        'final_exam_grade_qr_intisab',
        'second_round_grade',
        'total_grade',
        'daily_activities',
        'applications_avg',
        'semester_work_total',
        'semester_final_exam',
        'semester_total',
        'second_semester_final_exam',
        'both_semesters_final_total',
        'both_semesters_grand_total',
        'period1_activities',
        'period1_written',
        'period1_exam',
        'period1_total',
        'period2_activities',
        'period2_written',
        'period2_exam',
        'period2_total',
        'periods_combined_total',
        'year_end_exam',
        'final_grand_total',
        'passed_exam_rule',
        'passed_total_rule',
        'academic_status_note',
        'letter_grade',
        'status',
        'is_locked',
        'locked_at',
    ];

    protected $casts = [
        'coursework_grade' => 'decimal:2',
        'midterm_grade' => 'decimal:2',
        'final_exam_grade' => 'decimal:2',
        'final_exam_grade_qr_intisab' => 'decimal:2',
        'second_round_grade' => 'decimal:2',
        'total_grade' => 'decimal:2',
        'daily_activities' => 'decimal:2',
        'applications_avg' => 'decimal:2',
        'semester_work_total' => 'decimal:2',
        'semester_final_exam' => 'decimal:2',
        'semester_total' => 'decimal:2',
        'second_semester_final_exam' => 'decimal:2',
        'both_semesters_final_total' => 'decimal:2',
        'both_semesters_grand_total' => 'decimal:2',
        'period1_activities' => 'decimal:2',
        'period1_written' => 'decimal:2',
        'period1_exam' => 'decimal:2',
        'period1_total' => 'decimal:2',
        'period2_activities' => 'decimal:2',
        'period2_written' => 'decimal:2',
        'period2_exam' => 'decimal:2',
        'period2_total' => 'decimal:2',
        'periods_combined_total' => 'decimal:2',
        'year_end_exam' => 'decimal:2',
        'final_grand_total' => 'decimal:2',
        'passed_exam_rule' => 'boolean',
        'passed_total_rule' => 'boolean',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function gradeBatch(): BelongsTo
    {
        return $this->belongsTo(GradeBatch::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(GradeLog::class);
    }

    /**
     * Compute total grade and pass/fail status using the official Libyan Grading Logic Engine.
     */
    public function calculateResult(): void
    {
        app(\App\Services\GradeCalculationEngineService::class)->calculateGrade($this);
    }
}
