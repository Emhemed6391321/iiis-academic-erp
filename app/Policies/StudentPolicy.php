<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Student;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('students.view');
    }

    public function view(User $user, Student $student): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        return $user->hasPermission('students.view') && ((int)$user->branch_id === (int)$student->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('students.create');
    }

    public function update(User $user, Student $student): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        return $user->hasPermission('students.create') && ((int)$user->branch_id === (int)$student->branch_id);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->isSuperAdmin();
    }

    public function changeStatus(User $user, Student $student): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return $user->hasPermission('CHANGE_STUDENT_STATUS');
        }

        return $user->hasPermission('CHANGE_STUDENT_STATUS') && ((int)$user->branch_id === (int)$student->branch_id);
    }

    public function approveData(User $user, Student $student): bool
    {
        return $user->hasGlobalAccessScope() && $user->hasPermission('APPROVE_STUDENT_DATA');
    }
}
