<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosition extends Model
{
    protected $fillable = [
        'code',
        'title',
        'organizational_unit_id',
        'level_order',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level_order' => 'integer',
    ];

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    public function employeePlacements(): HasMany
    {
        return $this->hasMany(EmployeePlacement::class, 'job_position_id');
    }

    public function currentPlacement()
    {
        return $this->hasOne(EmployeePlacement::class, 'job_position_id')->where('is_current', true);
    }
}
