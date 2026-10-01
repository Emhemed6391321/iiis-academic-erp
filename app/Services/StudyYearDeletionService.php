<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\GradeBatch;
use App\Models\StudentGrade;
use App\Models\StudentAttendance;
use App\Models\Student;
use App\Models\OperationalWindow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class StudyYearDeletionService
{
    /**
     * Inspect study year relationships and compile a detailed dependencies report.
     */
    public function inspectDependencies(AcademicYear $year): array
    {
        $report = [];
        $totalRecords = 0;

        // 1. Current Active Year Check
        if ($year->is_current) {
            $report['is_current'] = [
                'count' => 1,
                'label' => 'العام الدراسي هو العام النشط حالياً والمعتمد لكافة العمليات',
                'critical' => true,
            ];
            $totalRecords++;
        }

        // 2. Locked / Archived Year Check
        if ($year->is_locked || strtoupper($year->status ?? '') === 'ARCHIVED') {
            $report['is_locked'] = [
                'count' => 1,
                'label' => 'العام الدراسي مقفل أو مؤرشف نهائياً في السجلات الرسمية',
                'critical' => true,
            ];
            $totalRecords++;
        }

        // 3. Student Grades & Grade Batches
        $gradeBatchesCount = GradeBatch::where('academic_year_id', $year->id)->count();
        $studentGradesCount = 0;
        if ($gradeBatchesCount > 0) {
            $studentGradesCount = StudentGrade::whereIn(
                'grade_batch_id',
                GradeBatch::where('academic_year_id', $year->id)->pluck('id')
            )->count();
        }
        if ($gradeBatchesCount > 0 || $studentGradesCount > 0) {
            $report['grades'] = [
                'count' => $studentGradesCount + $gradeBatchesCount,
                'label' => "سجلات درجات وامتحانات ({$studentGradesCount} درجة فردية و{$gradeBatchesCount} كشف رصد)",
                'critical' => true,
            ];
            $totalRecords += ($studentGradesCount + $gradeBatchesCount);
        }

        // 4. Student Attendance Records
        if (Schema::hasTable('student_attendance')) {
            $attendanceCount = StudentAttendance::where('academic_year_id', $year->id)->count();
            if ($attendanceCount > 0) {
                $report['attendance'] = [
                    'count' => $attendanceCount,
                    'label' => "سجلات حضور وغياب الطلاب ({$attendanceCount} سجل حضور)",
                    'critical' => true,
                ];
                $totalRecords += $attendanceCount;
            }
        }

        // 5. Enrolled Students
        $studentsCount = Student::where('enrolled_academic_year_id', $year->id)->count();
        if ($studentsCount > 0) {
            $report['students'] = [
                'count' => $studentsCount,
                'label' => "طلاب مقيدون رسمياً تحت قيد هذا العام ({$studentsCount} طالب)",
                'critical' => true,
            ];
            $totalRecords += $studentsCount;
        }

        // 6. Classes & Sections
        if (Schema::hasTable('academic_classes')) {
            $classesCount = DB::table('academic_classes')->where('academic_year_id', $year->id)->count();
            if ($classesCount > 0) {
                $report['classes'] = [
                    'count' => $classesCount,
                    'label' => "شعب وفصول دراسية ({$classesCount} شعبة)",
                    'critical' => true,
                ];
                $totalRecords += $classesCount;
            }
        }

        // 7. Rollover / Progression Logs
        if (Schema::hasTable('academic_year_rollover_logs')) {
            $rolloverCount = DB::table('academic_year_rollover_logs')
                ->where('source_academic_year_id', $year->id)
                ->orWhere('target_academic_year_id', $year->id)
                ->count();
            if ($rolloverCount > 0) {
                $report['rollover_logs'] = [
                    'count' => $rolloverCount,
                    'label' => "سجلات ترحيل وترقية دراسية ({$rolloverCount} عملية ترحيل)",
                    'critical' => true,
                ];
                $totalRecords += $rolloverCount;
            }
        }

        // 8. Workflow Requests
        if (Schema::hasTable('enrollment_status_requests')) {
            $requestsCount = DB::table('enrollment_status_requests')
                ->where('target_academic_year_id', $year->id)
                ->count();
            if ($requestsCount > 0) {
                $report['requests'] = [
                    'count' => $requestsCount,
                    'label' => "طلبات إيقاف وتجديد قيد طلابية ({$requestsCount} طلب)",
                    'critical' => true,
                ];
                $totalRecords += $requestsCount;
            }
        }

        // 9. Courses assigned to this year
        $coursesCount = Course::where('academic_year_id', $year->id)->count();
        if ($coursesCount > 0) {
            $report['courses'] = [
                'count' => $coursesCount,
                'label' => "مقررات دراسية مخصصة للعام ({$coursesCount} مقرر)",
                'critical' => false,
            ];
        }

        return [
            'can_delete' => ($totalRecords === 0),
            'total_blocking_records' => $totalRecords,
            'dependencies' => $report,
            'summary_message' => $totalRecords > 0
                ? "لا يمكن إزالة العام الدراسي «{$year->name}» لوجود ({$totalRecords}) سجلاً تشغيلياً وأكاديمياً مرتبطاً به."
                : "العام الدراسي «{$year->name}» خالٍ تماماً من أي ارتباطات تشغيلية أو دراسية، ويمكن إزالته بأمان.",
        ];
    }

    /**
     * Safely delete the study year inside a Database Transaction if and only if no blocking records exist.
     */
    public function safeDelete(AcademicYear $year, ?int $userId = null, ?string $ip = null): array
    {
        $inspection = $this->inspectDependencies($year);

        if (!$inspection['can_delete']) {
            return [
                'success' => false,
                'message' => $inspection['summary_message'],
                'inspection' => $inspection,
            ];
        }

        $yearId = $year->id;
        $yearName = $year->name;
        $yearCode = $year->code;

        DB::transaction(function () use ($year, $yearId, $yearName, $yearCode, $userId, $ip) {
            // Remove safe ancillary records
            Course::where('academic_year_id', $yearId)->delete();
            if (Schema::hasTable('academic_calendar_events')) {
                DB::table('academic_calendar_events')->where('academic_year_id', $yearId)->delete();
            }
            if (Schema::hasTable('academic_schedules')) {
                DB::table('academic_schedules')->where('academic_year_id', $yearId)->delete();
            }
            if (Schema::hasTable('operational_windows')) {
                OperationalWindow::where('academic_year_id', $yearId)->delete();
            }

            // Delete the study year
            $year->delete();

            // Record forensic audit log
            if (Schema::hasTable('system_audit_trails')) {
                try {
                    DB::table('system_audit_trails')->insert([
                        'user_id' => $userId,
                        'event_type' => 'ACADEMIC_YEAR_SAFE_DELETED',
                        'description' => "حذف آمن للعام الدراسي «{$yearName}» (رمز: {$yearCode}) بعد التحقق من خلوه التام من الارتباطات.",
                        'ip_address' => $ip,
                        'payload' => json_encode(['year_id' => $yearId, 'name' => $yearName, 'code' => $yearCode]),
                        'created_at' => Carbon::now(),
                    ]);
                } catch (\Exception $e) {}
            }
        });

        return [
            'success' => true,
            'message' => "تم حذف العام الدراسي «{$yearName}» بأمان وتوثيق العملية في سجل التدقيق.",
        ];
    }
}
