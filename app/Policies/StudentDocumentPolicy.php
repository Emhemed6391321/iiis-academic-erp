<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StudentDocument;
use App\Models\Student;

class StudentDocumentPolicy
{
    public function view(User $user, StudentDocument $document): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        $student = $document->student;
        return $student && ((int)$user->branch_id === (int)$student->branch_id);
    }

    public function create(User $user, Student $student): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        return (int)$user->branch_id === (int)$student->branch_id;
    }

    public function delete(User $user, StudentDocument $document): bool
    {
        if ($user->hasGlobalAccessScope()) {
            return true;
        }

        $student = $document->student;
        return $student && ((int)$user->branch_id === (int)$student->branch_id) && $user->hasPermission('students.create');
    }
}
