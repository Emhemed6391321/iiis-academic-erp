<?php

namespace App\Models\Traits;

use App\Models\Scopes\BranchScope;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    /**
     * Boot the BelongsToBranch trait for automatic scoping and attribute assignment.
     */
    protected static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            if (Auth::check()) {
                $user = Auth::user();
                if (!$user->hasGlobalAccessScope() && empty($model->branch_id) && !empty($user->branch_id)) {
                    $model->branch_id = $user->branch_id;
                }
            }
        });
    }

    /**
     * Relation to Branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
