<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StudentTransfer;
use App\Models\Student;

class StudentTransferPolicy
{
    /**
     * Determine whether the user can view the transfer.
     */
    public function view(User $user, StudentTransfer $transfer): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        return (int)$user->branch_id === (int)$transfer->from_branch_id ||
               (int)$user->branch_id === (int)$transfer->to_branch_id;
    }

    /**
     * Stage 1: Determine whether user can initiate a student transfer request.
     */
    public function create(User $user, Student $student): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        return $user->branch_id && (int)$user->branch_id === (int)$student->branch_id;
    }

    /**
     * Stage 2: Determine whether user can perform Academic Affairs review.
     */
    public function reviewAcademicAffairs(User $user, StudentTransfer $transfer): bool
    {
        // Four-eyes principle
        if ($transfer->requested_by && (int)$transfer->requested_by === (int)$user->id) {
            return false;
        }

        return $user->hasGlobalAccessScope() || $user->hasPermission('APPROVE_STUDENT_STATUS');
    }

    /**
     * Stage 3: Determine whether user can accept transfer on behalf of destination branch.
     */
    public function acceptDestination(User $user, StudentTransfer $transfer): bool
    {
        // Four-eyes principle
        if ($transfer->requested_by && (int)$transfer->requested_by === (int)$user->id) {
            return false;
        }

        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        return $user->branch_id && (int)$user->branch_id === (int)$transfer->to_branch_id;
    }

    /**
     * Stage 4: Determine whether user can grant final HQ sovereign approval.
     */
    public function approveFinal(User $user, StudentTransfer $transfer): bool
    {
        // Four-eyes principle
        if ($transfer->requested_by && (int)$transfer->requested_by === (int)$user->id) {
            return false;
        }

        return $user->hasGlobalAccessScope();
    }
}
