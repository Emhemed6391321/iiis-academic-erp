<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * إشعار يصل إلى الفرع المعني بنتيجة قرار على ملف طالب. المستخدم العام يرى إشعارات كل الفروع.
 */
class BranchNotice extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id', 'title', 'body', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->hasGlobalAccessScope() ? $query : $query->where('branch_id', $user->branch_id);
    }

    public static function notify(int $branchId, string $title, string $body): self
    {
        return static::create(['branch_id' => $branchId, 'title' => $title, 'body' => $body]);
    }
}
