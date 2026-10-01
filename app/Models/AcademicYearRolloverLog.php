<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicYearRolloverLog extends Model
{
    use HasFactory;

    protected $table = 'academic_year_rollover_logs';

    protected $fillable = [
        'from_academic_year_id',
        'to_academic_year_id',
        'executed_by',
        'students_evaluated',
        'students_promoted',
        'students_held_back',
        'students_graduated',
        'students_second_round',
        'courses_copied',
        'summary_json',
        'status',
        'notes',
    ];

    protected $casts = [
        'summary_json'          => 'array',
        'students_evaluated'    => 'integer',
        'students_promoted'     => 'integer',
        'students_held_back'    => 'integer',
        'students_graduated'    => 'integer',
        'students_second_round' => 'integer',
        'courses_copied'        => 'integer',
    ];

    public function fromYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'from_academic_year_id');
    }

    public function toYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'to_academic_year_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
