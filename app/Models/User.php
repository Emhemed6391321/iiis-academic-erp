<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'role_id',
        'national_id',
        'phone',
        'is_active',
        'two_factor_secret',
        'two_factor_enabled',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * الصلاحيات الخاصة والاستثنائية للمستخدم (منح أو حجب خاص).
     */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
                    ->withPivot('is_granted')
                    ->withTimestamps();
    }

    /**
     * سجل التسكينات الإدارية والوظيفية للمستخدم.
     */
    public function placements(): HasMany
    {
        return $this->hasMany(EmployeePlacement::class);
    }

    /**
     * التسكين الوظيفي الإداري الحالي الفعال للمستخدم.
     */
    public function currentPlacement(): HasOne
    {
        return $this->hasOne(EmployeePlacement::class)->where('is_current', true)->latestOfMany();
    }

    public function isSuperAdmin(): bool
    {
        if ($this->role) {
            return in_array(strtolower($this->role->name), ['super_admin']);
        }
        return false;
    }

    public function isHQ(): bool
    {
        return $this->isSuperAdmin() || ($this->role && $this->role->isGlobal());
    }

    public function hasGlobalAccessScope(): bool
    {
        return $this->isHQ();
    }

    /**
     * فحص الصلاحية مع احترام الترتيب الأمني:
     * 1. المدير العام يمتلك كافة الصلاحيات حكماً.
     * 2. الصلاحية الخاصة للمستخدم (إذا تم منحها أو حجبها استثنائياً).
     * 3. الصلاحية الموروثة من الدور الوظيفي.
     */
    public function hasPermission(string $permissionCode): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 1. فحص الصلاحية الخاصة المباشرة للمستخدم (Direct Override)
        $direct = $this->directPermissions->firstWhere('code', $permissionCode);
        if ($direct !== null) {
            return (bool) $direct->pivot->is_granted;
        }

        // 2. فحص الصلاحية الموروثة من الدور
        if (!$this->role) {
            return false;
        }

        return $this->role->permissions->contains('code', $permissionCode);
    }
}
