<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * Automatic isolation: branch users only see their branch's records.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // If user does not have global scope and belongs to a specific branch
            if (!$user->hasGlobalAccessScope() && !empty($user->branch_id)) {
                $builder->where($model->getTable() . '.branch_id', $user->branch_id);
            }
        }
    }
}
