<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AttendanceWarningNotice;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\SystemAuditTrail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AbsenceCalculationEngineService
{
    public const DEFAULT_SEMESTER_INSTRUCTIONAL_DAYS = 60;

    public const THRESHOLD_FIRST_WARNING  = 5.0;  // 5%
    public const THRESHOLD_SECOND_WARNING = 10.0; // 10%
    public const THRESHOLD_FINAL_WARNING  = 15.0; // 15%
    public const THRESHOLD_DEPRIVATION    = 20.0; // 20% (حد الحرمان المعتمد)

    /**
     * Compute total instructional days for a branch in the current semester.
     * Formula (Option B):
     * Total absence on total semester instructional days = (Total Absence Days / Total Semester Days) * 100%
     */
    public function getSemesterInstructionalDays(int $branchId, ?int $academicYearId = null): int
    {
        $branchRecordedDays = StudentAttendance::where('branch_id', $branchId)
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->distinct('record_date')
            ->count('record_date');

        return max($branchRecordedDays, self::DEFAULT_SEMESTER_INSTRUCTIONAL_DAYS);
    }

    /**
     * Evaluate a student's absence status and return calculation metrics.
     * 
     * @return array{
     *     total_semester_days: int,
     *     unexcused_days: int,
     *     excused_days: int,
     *     total_absence_days: int,
     *     absence_percentage: float,
     *     warning_level: string|null,
     *     is_deprived: bool,
     *     status_label: string
     * }
     */
    public function evaluateStudentAbsence(Student $student, ?int $academicYearId = null): array
    {
        $currentYear = $academicYearId 
            ? AcademicYear::find($academicYearId) 
            : AcademicYear::where('is_current', true)->first();

        $totalSemesterDays = $this->getSemesterInstructionalDays($student->branch_id, $currentYear?->id);

        $attendanceStats = StudentAttendance::where('student_id', $student->id)
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->selectRaw('
                SUM(CASE WHEN status IN (\'ABSENT\', \'ABSENT_UNEXCUSED\', \'absent\') THEN 1 ELSE 0 END) as unexcused_count,
                SUM(CASE WHEN status IN (\'EXCUSED\', \'ABSENT_EXCUSED\', \'excused\') THEN 1 ELSE 0 END) as excused_count
            ')
            ->first();

        return $this->buildMetrics(
            $totalSemesterDays,
            (int) ($attendanceStats->unexcused_count ?? 0),
            (int) ($attendanceStats->excused_count ?? 0)
        );
    }

    /**
     * Same result as evaluateStudentAbsence() for many students using a fixed number of
     * queries (instead of ~3 per student). Keyed by student id.
     *
     * @param  iterable<Student>  $students
     * @return array<int, array>
     */
    public function evaluateStudents(iterable $students, ?int $academicYearId = null): array
    {
        $currentYear = $academicYearId
            ? AcademicYear::find($academicYearId)
            : AcademicYear::where('is_current', true)->first();

        $students = collect($students);
        if ($students->isEmpty()) {
            return [];
        }

        $counts = StudentAttendance::whereIn('student_id', $students->pluck('id')->all())
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->selectRaw('
                student_id,
                SUM(CASE WHEN status IN (\'ABSENT\', \'ABSENT_UNEXCUSED\', \'absent\') THEN 1 ELSE 0 END) as unexcused_count,
                SUM(CASE WHEN status IN (\'EXCUSED\', \'ABSENT_EXCUSED\', \'excused\') THEN 1 ELSE 0 END) as excused_count
            ')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $daysByBranch = [];
        $result = [];
        foreach ($students as $student) {
            $daysByBranch[$student->branch_id] ??= $this->getSemesterInstructionalDays($student->branch_id, $currentYear?->id);
            $row = $counts->get($student->id);
            $result[$student->id] = $this->buildMetrics(
                $daysByBranch[$student->branch_id],
                (int) ($row->unexcused_count ?? 0),
                (int) ($row->excused_count ?? 0)
            );
        }

        return $result;
    }

    private function buildMetrics(int $totalSemesterDays, int $unexcusedDays, int $excusedDays): array
    {
        $totalAbsenceDays = $unexcusedDays; // الحرمان يُحسب على الغياب غير المبرر

        $absencePercentage = $totalSemesterDays > 0 
            ? round(($totalAbsenceDays / $totalSemesterDays) * 100, 2) 
            : 0.0;

        $warningLevel = null;
        $statusLabel = 'منتظم';
        $isDeprived = false;

        if ($absencePercentage >= self::THRESHOLD_DEPRIVATION) {
            $warningLevel = 'EXPULSION_NOTICE';
            $statusLabel = 'محروم رسمياً لتجاوز نصاب الغياب (20% فأكثر)';
            $isDeprived = true;
        } elseif ($absencePercentage >= self::THRESHOLD_FINAL_WARNING) {
            $warningLevel = 'FINAL_WARNING';
            $statusLabel = 'إنذار نهائي (خطر الحرمان 15%)';
        } elseif ($absencePercentage >= self::THRESHOLD_SECOND_WARNING) {
            $warningLevel = 'SECOND_WARNING';
            $statusLabel = 'إنذار غياب ثانٍ (10%)';
        } elseif ($absencePercentage >= self::THRESHOLD_FIRST_WARNING) {
            $warningLevel = 'FIRST_WARNING';
            $statusLabel = 'إنذار غياب أول (5%)';
        }

        return [
            'total_semester_days' => $totalSemesterDays,
            'unexcused_days'      => $unexcusedDays,
            'excused_days'        => $excusedDays,
            'total_absence_days'  => $totalAbsenceDays,
            'absence_percentage'  => $absencePercentage,
            'warning_level'       => $warningLevel,
            'is_deprived'         => $isDeprived,
            'status_label'        => $statusLabel,
        ];
    }

    /**
     * Issue an automatic warning or deprivation notice if thresholds are exceeded.
     */
    public function issueNoticeIfEligible(Student $student, ?User $issuer = null): ?AttendanceWarningNotice
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        $metrics = $this->evaluateStudentAbsence($student, $currentYear?->id);

        if (!$metrics['warning_level']) {
            return null;
        }

        $targetLevel = $metrics['warning_level'];

        // Check if notice already issued for this level
        $alreadyIssued = AttendanceWarningNotice::where('student_id', $student->id)
            ->where('warning_level', $targetLevel)
            ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
            ->exists();

        if ($alreadyIssued) {
            return null;
        }

        $noticeNumber = 'إنذار-' . substr($targetLevel, 0, 4) . '-' . date('Y') . '-' . str_pad($student->id, 5, '0', STR_PAD_LEFT);
        $issuerId = $issuer?->id ?? Auth::id() ?? 1;

        $statement = match ($targetLevel) {
            'EXPULSION_NOTICE' => "قرار حرمان رسمي: تجاوز الطالب نسبة غياب " . self::THRESHOLD_DEPRIVATION . "% ({$metrics['total_absence_days']} أيام من أصل {$metrics['total_semester_days']} يوماً دراسياً). يُحرم الطالب من دخول الامتحانات ويُقفل السجل نهائياً.",
            'FINAL_WARNING'    => "إنذار نهائي: بلغت نسبة غياب الطالب {$metrics['absence_percentage']}% ({$metrics['total_absence_days']} أيام). الطالب على حافة الحرمان النهائي.",
            'SECOND_WARNING'   => "إنذار ثانٍ: بلغت نسبة غياب الطالب {$metrics['absence_percentage']}% ({$metrics['total_absence_days']} أيام). يلزم حضور ولي الأمر للفرع.",
            'FIRST_WARNING'    => "إنذار أول: بلغت نسبة غياب الطالب {$metrics['absence_percentage']}% ({$metrics['total_absence_days']} أيام). تنبيه للانضباط الأكاديمي.",
            default            => "إشعار غياب للطالب برقم {$noticeNumber}.",
        };

        return DB::transaction(function () use ($student, $currentYear, $noticeNumber, $targetLevel, $metrics, $statement, $issuerId) {
            $notice = AttendanceWarningNotice::create([
                'student_id'           => $student->id,
                'branch_id'            => $student->branch_id,
                'academic_year_id'     => $currentYear?->id,
                'notice_number'        => $noticeNumber,
                'warning_level'        => $targetLevel,
                'unexcused_days_count' => $metrics['unexcused_days'],
                'total_absence_days'   => $metrics['total_absence_days'],
                'absence_percentage'   => $metrics['absence_percentage'],
                'notice_date'          => Carbon::today()->format('Y-m-d'),
                'admin_statement'      => $statement,
                'delivery_status'      => 'PENDING',
                'issued_by'            => $issuerId,
            ]);

            // Enforce retroactive edit lock on absent records
            StudentAttendance::where('student_id', $student->id)
                ->whereIn('status', ['absent', 'ABSENT', 'ABSENT_UNEXCUSED'])
                ->whereNull('modification_reason')
                ->update([
                    'modification_reason' => "مغلق آلياً بموجب إشعار الحرمان {$noticeNumber} — يمنع التعديل الرجعي إلا باعتماد مركزي",
                ]);

            SystemAuditTrail::log(
                eventType: 'ABSENCE_NOTICE_GENERATED',
                description: "توليد إشعار غياب/حرمان رسمي ({$targetLevel}) للطالب {$student->full_name} بنسبة ({$metrics['absence_percentage']}%).",
                payload: [
                    'student_id'   => $student->id,
                    'notice_id'    => $notice->id,
                    'notice_no'    => $noticeNumber,
                    'level'        => $targetLevel,
                    'percentage'   => $metrics['absence_percentage'],
                    'days_absent'  => $metrics['total_absence_days'],
                    'semester_days'=> $metrics['total_semester_days'],
                ],
                userId: $issuerId,
                branchId: $student->branch_id,
                severity: $metrics['is_deprived'] ? 'CRITICAL' : 'WARNING'
            );

            return $notice;
        });
    }
}
