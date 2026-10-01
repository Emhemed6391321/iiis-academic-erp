<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Branch;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Branch $branch): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        return (int)$user->branch_id === (int)$branch->id;
    }

    public function update(User $user, Branch $branch): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return $user->hasPermission('branches.manage');
        }

        return (int)$user->branch_id === (int)$branch->id && $user->hasPermission('branches.manage');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }
}
