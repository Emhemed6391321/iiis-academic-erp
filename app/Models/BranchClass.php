<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToBranch;

class BranchClass extends Model
{
    use HasFactory, BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'name',
        'academic_year',
        'stage',
        'room_type',
        'floor',
        'max_capacity',
        'current_students',
        'available_seats',
        'status',
        'equipment',
        'notes',
    ];

    protected $casts = [
        'max_capacity' => 'integer',
        'current_students' => 'integer',
        'available_seats' => 'integer',
        'equipment' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
