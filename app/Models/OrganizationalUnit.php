<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationalUnit extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'sort_order',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class, 'parent_id')->orderBy('sort_order');
    }

    public function jobPositions(): HasMany
    {
        return $this->hasMany(JobPosition::class, 'organizational_unit_id');
    }

    public function employeePlacements(): HasMany
    {
        return $this->hasMany(EmployeePlacement::class, 'organizational_unit_id');
    }
}
