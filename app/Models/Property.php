<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Traits\BelongsToBranch;

class Property extends Model
{
    use HasFactory, BelongsToBranch;

    protected $fillable = [
        'property_number',
        'name',
        'type',
        'address',
        'city',
        'region',
        'latitude',
        'longitude',
        'area_sqm',
        'floors_count',
        'halls_count',
        'offices_count',
        'bathrooms_count',
        'yards_count',
        'labs_count',
        'has_library',
        'has_mosque',
        'storage_count',
        'parking_capacity',
        'owner_name',
        'owner_contact',
        'property_status',
        'usage_status',
        'branch_id',
        'notes',
        'photos',
        'legal_documents',
    ];

    protected $casts = [
        'area_sqm' => 'decimal:2',
        'has_library' => 'boolean',
        'has_mosque' => 'boolean',
        'photos' => 'array',
        'legal_documents' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(BranchContract::class, 'property_id')->orderBy('id', 'desc');
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(BranchContract::class, 'property_id')
            ->whereIn('status', ['ACTIVE', 'active', 'EXPIRING_SOON', 'near_expiry'])
            ->latestOfMany();
    }
}
