<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'inspector_id',
        'inspector_name',
        'assessment_date',
        'structure_safety_score',
        'classrooms_capacity_score',
        'facilities_hygiene_score',
        'it_connectivity_score',
        'admin_compliance_score',
        'total_score',
        'rating_grade',
        'compliance_status',
        'strengths',
        'recommendations',
        'notes',
        'checklist_data',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'checklist_data' => 'array',
        'structure_safety_score' => 'integer',
        'classrooms_capacity_score' => 'integer',
        'facilities_hygiene_score' => 'integer',
        'it_connectivity_score' => 'integer',
        'admin_compliance_score' => 'integer',
        'total_score' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
