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
            
            // If user does not have global scope, isolate strictly to their branch
            if (!$user->hasGlobalAccessScope()) {
                $branchId = $user->branch_id ?? -1;
                $builder->where($model->getTable() . '.branch_id', $branchId);
            }
        }
    }
}
