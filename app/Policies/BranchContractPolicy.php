<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BranchContract;

class BranchContractPolicy
{
    public function view(User $user, BranchContract $contract): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        return (int)$user->branch_id === (int)$contract->branch_id;
    }

    public function update(User $user, BranchContract $contract): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        return (int)$user->branch_id === (int)$contract->branch_id;
    }

    public function delete(User $user, BranchContract $contract): bool
    {
        return $user->isSuperAdmin();
    }
}
