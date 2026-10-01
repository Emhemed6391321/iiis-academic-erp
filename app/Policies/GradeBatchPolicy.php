<?php

namespace App\Policies;

use App\Models\User;
use App\Models\GradeBatch;

class GradeBatchPolicy
{
    public function view(User $user, GradeBatch $batch): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        return (int)$user->branch_id === (int)$batch->branch_id;
    }

    public function update(User $user, GradeBatch $batch): bool
    {
        if ($batch->is_locked) {
            return false;
        }

        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        return (int)$user->branch_id === (int)$batch->branch_id;
    }

    /**
     * Four-Eyes Principle (Requirement 1.8):
     * A user cannot approve a grade batch they submitted or created themselves.
     */
    public function approve(User $user, GradeBatch $batch): bool
    {
        if (!$user->hasGlobalAccessScope() || !$user->hasPermission('grades.approve_hq')) {
            return false;
        }

        // Four-eyes rule: Approver must be distinct from submitter/creator
        if ((int)$batch->submitted_by === (int)$user->id) {
            return false;
        }

        return true;
    }
}
