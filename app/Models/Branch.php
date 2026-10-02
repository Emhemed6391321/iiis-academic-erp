<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'city',
        'address',
        'geo_location',
        'latitude',
        'longitude',
        'map_url',
        'phone',
        'email',
        'manager_name',
        'building_type',
        'building_condition',
        'total_staff',
        'academic_staff',
        'admin_staff',
        'latest_score',
        'latest_rating',
        'notes',
        'is_active',
        'branch_status',
        'short_name',
        'branch_type',
        'gender_type',
        'region',
        'manager_phone',
        'manager_email',
        'established_date',
        'operating_date',
        'suspended_at',
        'suspension_reason',
        'facebook_url',
        'telegram_url',
        'whatsapp_number',
        'website_url',
        'cover_image',
        'photos',
        'social_links',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'latest_score' => 'integer',
        'total_staff' => 'integer',
        'academic_staff' => 'integer',
        'admin_staff' => 'integer',
        'photos' => 'array',
        'social_links' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function gradeBatches(): HasMany
    {
        return $this->hasMany(GradeBatch::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(BranchAssessment::class)->orderBy('assessment_date', 'desc');
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(BranchFacility::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(BranchClass::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(BranchRequest::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(BranchContract::class);
    }
}
