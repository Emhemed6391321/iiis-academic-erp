<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\AttendanceWarningNotice;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\SystemAuditTrail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MonitorAbsenceThresholds extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'attendance:monitor-absence-thresholds {--min-days=60 : إجمالي أيام الفصل المعتمدة لحساب النسبة}';

    /**
     * The console command description.
     */
    protected $description = 'الرصد الذكي لغياب الطلاب وإصدار إنذارات الحرمان الآلية (5%، 10%، 15%) وتطبيق حظر التعديل بأثر رجعي';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('بدء التحليل التنبؤي لمعدلات غياب الطلاب وتطبيق عتبات الإنذار الذكي...');

        $currentYear = AcademicYear::where('is_current', true)->first();
        $adminUser = User::where('role', 'SUPER_ADMIN')->first() ?? User::first();
        $defaultSemesterDays = (int) $this->option('min-days');

        // Active students
        $students = Student::where('academic_status', 'ENROLLED_ACTIVE')->get();
        $issuedCount = 0;
        $lockedCount = 0;

        foreach ($students as $student) {
            // Count total distinct session days for student's branch or fallback
            $branchDaysCount = StudentAttendance::where('branch_id', $student->branch_id)
                ->distinct('record_date')
                ->count('record_date');

            $totalExpectedDays = max($branchDaysCount, $defaultSemesterDays);

            // Count unexcused absences
            $absentCount = StudentAttendance::where('student_id', $student->id)
                ->whereIn('status', ['absent', 'ABSENT'])
                ->count();

            if ($absentCount === 0) {
                continue;
            }

            $absencePercentage = round(($absentCount / $totalExpectedDays) * 100, 2);

            // Determine threshold level
            $targetLevel = null;
            if ($absencePercentage >= 15.0 || $absentCount >= 9) {
                $targetLevel = 'FINAL_WARNING';
            } elseif ($absencePercentage >= 10.0 || $absentCount >= 6) {
                $targetLevel = 'SECOND_WARNING';
            } elseif ($absencePercentage >= 5.0 || $absentCount >= 3) {
                $targetLevel = 'FIRST_WARNING';
            }

            if (!$targetLevel) {
                continue;
            }

            // Check if this notice level was already issued
            $alreadyIssued = AttendanceWarningNotice::where('student_id', $student->id)
                ->where('warning_level', $targetLevel)
                ->when($currentYear, fn($q) => $q->where('academic_year_id', $currentYear->id))
                ->exists();

            if (!$alreadyIssued) {
                $noticeNumber = 'WARN-' . $student->id . '-' . substr($targetLevel, 0, 3) . '-' . date('Ymd');
                $statement = match ($targetLevel) {
                    'FINAL_WARNING'  => "إنذار نهائي: تجاوز الطالب نسبة غياب 15% ({$absentCount} أيام). دخل الطالب مرحلة خطر الحرمان والشطب الأكاديمي، وتم قفل التعديل بأثر رجعي.",
                    'SECOND_WARNING' => "إنذار ثانٍ: تجاوز الطالب نسبة غياب 10% ({$absentCount} أيام). يلزم حضور ولي الأمر للفرع لمتابعة الوضع الدراسي.",
                    'FIRST_WARNING'  => "إنذار أول: تجاوز الطالب نسبة غياب 5% ({$absentCount} أيام). تنبيه للمحافظة على الانضباط الأكاديمي.",
                };

                AttendanceWarningNotice::create([
                    'student_id'           => $student->id,
                    'branch_id'            => $student->branch_id,
                    'academic_year_id'     => $currentYear?->id,
                    'notice_number'        => $noticeNumber,
                    'warning_level'        => $targetLevel,
                    'unexcused_days_count' => $absentCount,
                    'total_absence_days'   => $absentCount,
                    'absence_percentage'   => $absencePercentage,
                    'notice_date'          => Carbon::today()->format('Y-m-d'),
                    'admin_statement'      => $statement,
                    'delivery_status'      => 'PENDING',
                    'issued_by'            => $adminUser?->id ?? 1,
                ]);

                // Enforce retroactive edit lock:
                // Set modification_reason to lock records against branch tampering
                StudentAttendance::where('student_id', $student->id)
                    ->whereIn('status', ['absent', 'ABSENT'])
                    ->whereNull('modification_reason')
                    ->update([
                        'modification_reason' => "مغلق آلياً بقرار إنذار الحرمان {$noticeNumber} — لا يُعدل إلا بموافقة مركزية رسمية",
                    ]);

                SystemAuditTrail::log(
                    'ABSENCE_WARNING_TRIGGER',
                    "رصد آلي: إصدار {$statement} وحظر تعديل سجلات الحضور بأثر رجعي.",
                    [
                        'student_id'         => $student->id,
                        'absent_count'       => $absentCount,
                        'percentage'         => $absencePercentage,
                        'level'              => $targetLevel,
                        'notice_number'      => $noticeNumber,
                    ],
                    $adminUser?->id ?? 1,
                    $student->branch_id
                );

                $issuedCount++;
                $lockedCount += $absentCount;
                $this->line("  ✓ إصدار [{$targetLevel}] للطالب ID {$student->id} (نسبة الغياب: {$absencePercentage}%)");
            }
        }

        $this->info("اكتمل فحص الغياب: تم إصدار {$issuedCount} إنذاراً ذكياً وقفل سجلات الحضور ذات الصلة بأثر رجعي.");
        return Command::SUCCESS;
    }
}
