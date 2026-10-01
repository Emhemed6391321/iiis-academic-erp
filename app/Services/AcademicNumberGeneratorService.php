<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Branch;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AcademicNumberGeneratorService
{
    /**
     * Generate standard official enrollment number format:
     * [Gender: 1=Male, 2=Female][Year Code: 2-digits, e.g. 26][4-digit Sequence: 0001-9999]
     * Example: 1260001
     */
    public function generate(Student $student): string
    {
        return DB::transaction(function () use ($student) {
            // 1. رقم الجنس: 1 للذكر، 2 للأنثى
            $genderDigit = ($student->gender === 'FEMALE' || $student->gender === '2') ? '2' : '1';

            // 2. كود العام الدراسي (خانتبن) مثل: 26
            $yearCode = Carbon::now()->format('y'); // 2-digit year e.g. '26'
            
            $academicYear = $student->enrolledAcademicYear ?? AcademicYear::where('is_current', true)->first();
            if ($academicYear) {
                if (preg_match('/(?:20)?(\d{2})/', $academicYear->code ?? '', $m)) {
                    $yearCode = $m[1];
                } elseif (preg_match('/(\d{2})/', $academicYear->name ?? '', $m)) {
                    $yearCode = $m[1];
                }
            }

            // 3. البادئة: رقم الجنس + كود العام الدراسي (مثلاً: 126 أو 226)
            $prefix = "{$genderDigit}{$yearCode}";

            // Find current maximum sequence for this prefix
            $latestNumber = Student::where('academic_number', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->orderBy('academic_number', 'DESC')
                ->value('academic_number');

            $nextSequence = 1;
            if ($latestNumber && strlen($latestNumber) >= (strlen($prefix) + 4)) {
                $currentSeq = (int) substr($latestNumber, -4);
                $nextSequence = $currentSeq + 1;
            }

            $formattedSeq = str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);

            return "{$prefix}{$formattedSeq}";
        });
    }
}
