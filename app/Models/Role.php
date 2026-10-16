<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'scope_type',
        'description',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * الصفة الوظيفية المعتمدة المرتبطة بهذا الدور (مسماها = display_name للدور).
     */
    public function jobPosition(): HasOne
    {
        return $this->hasOne(JobPosition::class, 'role_id');
    }

    public function isGlobal(): bool
    {
        return $this->scope_type === 'GLOBAL_SCOPE';
    }
}
