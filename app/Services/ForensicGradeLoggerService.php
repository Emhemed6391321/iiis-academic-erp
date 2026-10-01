<?php

namespace App\Services;

use App\Models\StudentGrade;
use App\Models\GradeLog;
use Illuminate\Support\Facades\Auth;

class ForensicGradeLoggerService
{
    /**
     * Audit and log changes on a student grade entry.
     */
    public function logChange(
        StudentGrade $grade,
        string $modifiedField,
        $oldValue,
        $newValue,
        string $ipAddress,
        ?string $userAgent = null,
        ?string $reason = null
    ): ?GradeLog {
        // Normalize strings for comparison
        $oldNorm = is_null($oldValue) ? null : (string) (float) $oldValue;
        $newNorm = is_null($newValue) ? null : (string) (float) $newValue;

        if ($oldNorm === $newNorm) {
            return null;
        }

        $user = Auth::user();
        $userId = $user ? $user->id : 1;
        $branchId = $grade->gradeBatch->branch_id ?? ($user ? $user->branch_id : 1);

        return GradeLog::create([
            'student_grade_id' => $grade->id,
            'student_id'       => $grade->student_id,
            'course_id'        => $grade->course_id,
            'branch_id'        => $branchId,
            'modified_field'   => $modifiedField,
            'old_value'        => $oldValue !== null ? (string) $oldValue : null,
            'new_value'        => $newValue !== null ? (string) $newValue : null,
            'user_id'          => $userId,
            'ip_address'       => $ipAddress,
            'user_agent'       => $userAgent,
            'reason'           => $reason ?? 'رصد درجات اعتيادي عبر شاشة الكنترول',
        ]);
    }
}
