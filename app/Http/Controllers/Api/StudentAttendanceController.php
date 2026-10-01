<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\AttendanceWarningNotice;
use App\Models\Branch;
use App\Models\StudyYear;
use App\Models\Department;
use App\Models\AcademicYear;
use App\Models\SystemAuditTrail;
use App\Services\AbsenceCalculationEngineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentAttendanceController extends Controller
{
    /**
     * الأيام باللغة العربية
     */
    protected array $arabicDays = [
        0 => 'الأحد',
        1 => 'الإثنين',
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
        6 => 'السبت',
    ];

    /**
     * 1. شبكة رصد الحضور اليومي السريع لفرع ومرحلة وشعبة وتاريخ
     */
    public function getDailySheet(Request $request): JsonResponse
    {
        $dateStr = $request->get('date', Carbon::today()->format('Y-m-d'));
        $recordDate = Carbon::parse($dateStr);
        $dayOfWeek = $this->arabicDays[$recordDate->dayOfWeek] ?? 'اليوم';

        $branchId = $request->get('branch_id');
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && !empty($user->branch_id)) {
            $branchId = $user->branch_id;
        }

        $studyYearId = $request->get('study_year_id');
        $departmentId = $request->get('department_id');

        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYearId = $request->get('academic_year_id', $currentYear?->id);

        // جلب قائمة الطلاب المقيدين بالفصل
        $studentsQuery = Student::query()
            ->with(['branch:id,name,code', 'currentStudyYear:id,name,level_order', 'department:id,name,code'])
            ->where(function($q) {
                $q->where('academic_status', 'ENROLLED_ACTIVE')
                  ->orWhere('academic_status', 'ACTIVE')
                  ->orWhereNull('academic_status');
            });

        if ($branchId) {
            $studentsQuery->where('branch_id', $branchId);
        }
        if ($studyYearId) {
            $studentsQuery->where('current_study_year_id', $studyYearId);
        }
        if ($departmentId) {
            $studentsQuery->where('department_id', $departmentId);
        }

        $students = $studentsQuery->orderBy('full_name')->get();

        // جلب سجلات الحضور المسجلة مسبقًا لهذا التاريخ
        $existingRecords = StudentAttendance::forDate($dateStr)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $sheetData = [];
        $presentCount = 0;
        $absentUnexcusedCount = 0;
        $absentExcusedCount = 0;
        $lateCount = 0;
        $earlyDepartureCount = 0;
        $notRecordedCount = 0;

        foreach ($students as $student) {
            $rec = $existingRecords->get($student->id);

            if ($rec) {
                $status = $rec->status;
                if (in_array($status, ['PRESENT'])) $presentCount++;
                elseif (in_array($status, ['ABSENT', 'ABSENT_UNEXCUSED'])) $absentUnexcusedCount++;
                elseif (in_array($status, ['EXCUSED', 'ABSENT_EXCUSED'])) $absentExcusedCount++;
                elseif ($status === 'LATE') $lateCount++;
                elseif ($status === 'EARLY_DEPARTURE') $earlyDepartureCount++;
            } else {
                $status = 'NOT_SET';
                $notRecordedCount++;
            }

            $sheetData[] = [
                'student_id'        => $student->id,
                'full_name'         => $student->full_name,
                'academic_number'   => $student->academic_number ?: ('(' . $student->id . ')'),
                'national_id'       => $student->national_id,
                'gender'            => $student->gender === 'MALE' ? 'ذكر' : 'أنثى',
                'study_type'        => $student->study_type === 'INTISAB' ? 'انتساب' : 'نظامي',
                'branch_name'       => $student->branch?->name ?: '—',
                'stage_name'        => $student->currentStudyYear?->name ?: '—',
                'section_name'      => $student->department?->name ?: '—',
                'photo_url'         => $student->profile_photo_path ? asset('storage/' . $student->profile_photo_path) : null,
                
                // تفاصيل سجل الحضور
                'attendance_id'     => $rec?->id,
                'status'            => $rec ? $rec->status : 'NOT_SET', // NOT_SET = لم يُرصد بعد
                'is_recorded'       => !is_null($rec),
                'late_minutes'      => $rec?->late_minutes ?? 0,
                'departure_status'  => $rec?->departure_status ?? 'NOT_DEPARTED',
                'check_in_time'     => $rec?->check_in_time ?? '08:00',
                'check_out_time'    => $rec?->check_out_time,
                'departure_reason'  => $rec?->departure_reason ?? '',
                'absence_reason'    => $rec?->absence_reason ?? '',
                'verification_method' => $rec?->verification_method ?? 'MANUAL',
                'early_permission_slip_number' => $rec?->early_permission_slip_number,
                'early_permission_reason' => $rec?->early_permission_reason,
                'early_permission_guardian_name' => $rec?->early_permission_guardian_name,
                'early_permission_guardian_phone' => $rec?->early_permission_guardian_phone,
                'early_permission_authorized_by' => $rec?->early_permission_authorized_by,
                'early_permission_notes' => $rec?->early_permission_notes,
                'recorded_by_name'  => $rec?->recorder?->name ?? '—',
            ];
        }

        return response()->json([
            'success' => true,
            'meta' => [
                'record_date'       => $dateStr,
                'day_of_week'       => $dayOfWeek,
                'academic_year_id'  => $academicYearId,
                'branch_id'         => $branchId,
                'study_year_id'     => $studyYearId,
                'department_id'     => $departmentId,
            ],
            'summary' => [
                'total_students'      => $students->count(),
                'present'             => $presentCount,
                'absent_unexcused'    => $absentUnexcusedCount,
                'absent_excused'      => $absentExcusedCount,
                'late'                => $lateCount,
                'early_departure'     => $earlyDepartureCount,
                'recorded_total'      => $existingRecords->count(),
                'not_recorded_total'  => $notRecordedCount,
                'attendance_rate'     => $students->count() > 0 ? round((($presentCount + $lateCount) / $students->count()) * 100, 1) : 0,
            ],
            'students' => $sheetData,
            'filters' => [
                'branches'       => Branch::where('is_active', true)->get(['id', 'name', 'code']),
                'study_years'    => StudyYear::orderBy('level_order')->get(['id', 'name', 'level_order']),
                'departments'    => Department::where('is_active', true)->get(['id', 'name', 'code']),
                'academic_years' => AcademicYear::orderBy('id', 'desc')->get(['id', 'name', 'is_current']),
            ]
        ]);
    }

    /**
     * 2. حفظ / تحديث رصد الحضور الجماعي لطلاب الفصل
     */
    public function batchSave(Request $request): JsonResponse
    {
        $request->validate([
            'date'    => 'required|date',
            'records' => 'required|array',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status'     => 'required|string',
        ]);

        $user = Auth::user();
        $dateStr = $request->input('date');
        $recordDate = Carbon::parse($dateStr);
        $dayOfWeek = $this->arabicDays[$recordDate->dayOfWeek] ?? 'اليوم';

        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYearId = $request->input('academic_year_id', $currentYear?->id);

        $records = $request->input('records');
        $savedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($records as $item) {
                $student = Student::find($item['student_id']);
                if (!$student) continue;

                // عزل الفروع: التأكد من أن الطالب يتبع نفس فرع المستخدم إذا لم يكن الإدارة العامة
                if ($user && !$user->hasGlobalAccessScope() && !empty($user->branch_id)) {
                    if ((int)$student->branch_id !== (int)$user->branch_id) {
                        continue;
                    }
                }

                $status = $item['status'] ?? 'PRESENT';
                $lateMinutes = (int)($item['late_minutes'] ?? 0);
                $departureStatus = $item['departure_status'] ?? 'NOT_DEPARTED';
                $checkInTime = $item['check_in_time'] ?? ($status === 'PRESENT' || $status === 'LATE' ? '08:00:00' : null);
                $checkOutTime = $item['check_out_time'] ?? null;
                $departureReason = $item['departure_reason'] ?? null;
                $absenceReason = $item['absence_reason'] ?? null;

                $existing = StudentAttendance::where('student_id', $student->id)
                    ->forDate($dateStr)
                    ->first();

                $attributes = [
                    'branch_id'           => $student->branch_id,
                    'academic_year_id'    => $academicYearId,
                    'study_year_id'       => $student->current_study_year_id,
                    'department_id'       => $student->department_id,
                    'day_of_week'         => $dayOfWeek,
                    'status'              => $status,
                    'late_minutes'        => $lateMinutes,
                    'departure_status'    => $departureStatus,
                    'check_in_time'       => $checkInTime,
                    'check_out_time'      => $checkOutTime,
                    'departure_reason'    => $departureReason,
                    'absence_reason'      => $absenceReason,
                    'recorded_by'         => $user?->id,
                    'updated_by'          => $user?->id,
                ];

                try {
                    if ($existing) {
                        $existing->update($attributes);
                    } else {
                        StudentAttendance::create(array_merge([
                            'student_id'  => $student->id,
                            'record_date' => Carbon::parse($dateStr)->format('Y-m-d'),
                        ], $attributes));
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'UNIQUE')) {
                        $existing = StudentAttendance::where('student_id', $student->id)->forDate($dateStr)->first();
                        if ($existing) {
                            $existing->update($attributes);
                        }
                    } else {
                        throw $e;
                    }
                }
                $savedCount++;
            }

            // تسجيل العملية في سجل التدقيق الأمني
            try {
                SystemAuditTrail::create([
                    'user_id'     => $user?->id,
                    'event_type'  => 'STUDENT_ATTENDANCE_BATCH_SAVED',
                    'description' => "قام المستخدم " . ($user?->name ?? 'مسؤول الحضور') . " بحفظ رصد الحضور لعدد ({$savedCount}) طالب بتاريخ: {$dateStr}",
                    'ip_address'  => request()->ip(),
                    'payload'     => [
                        'record_date'   => $dateStr,
                        'count_saved'   => $savedCount,
                        'branch_id'     => $request->input('branch_id'),
                        'saved_at'      => Carbon::now()->toIso8601String(),
                    ],
                    'created_at'  => Carbon::now(),
                ]);
            } catch (\Exception $e) {}

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "تم حفظ وتثبيت سجل الحضور بنجاح لعدد ({$savedCount}) طالب.",
                'saved_count' => $savedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ سجل الحضور: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 3. تسجيل أو تعديل حركة حضور/انصراف فردية لطالب
     */
    public function singleRecord(Request $request, Student $student): JsonResponse
    {
        $request->validate([
            'record_date' => 'required|date',
            'status'      => 'required|string',
        ]);

        $user = Auth::user();
        $dateStr = $request->input('record_date');
        $recordDate = Carbon::parse($dateStr);
        $dayOfWeek = $this->arabicDays[$recordDate->dayOfWeek] ?? 'اليوم';

        $currentYear = AcademicYear::where('is_current', true)->first();

        $existing = StudentAttendance::where('student_id', $student->id)
            ->forDate($dateStr)
            ->first();

        $attributes = [
            'branch_id'           => $student->branch_id,
            'academic_year_id'    => $request->input('academic_year_id', $currentYear?->id),
            'study_year_id'       => $student->current_study_year_id,
            'department_id'       => $student->department_id,
            'day_of_week'         => $dayOfWeek,
            'status'              => $request->input('status'),
            'late_minutes'        => (int)$request->input('late_minutes', 0),
            'departure_status'    => $request->input('departure_status', 'NOT_DEPARTED'),
            'check_in_time'       => $request->input('check_in_time'),
            'check_out_time'      => $request->input('check_out_time'),
            'departure_reason'    => $request->input('departure_reason'),
            'absence_reason'      => $request->input('absence_reason'),
            'modification_reason' => $request->input('modification_reason'),
            'recorded_by'         => $user?->id,
            'updated_by'          => $user?->id,
        ];

        try {
            if ($existing) {
                $existing->update($attributes);
                $attendance = $existing;
            } else {
                $attendance = StudentAttendance::create(array_merge([
                    'student_id'  => $student->id,
                    'record_date' => Carbon::parse($dateStr)->format('Y-m-d'),
                ], $attributes));
            }
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'UNIQUE')) {
                $existing = StudentAttendance::where('student_id', $student->id)->forDate($dateStr)->first();
                if ($existing) {
                    $existing->update($attributes);
                    $attendance = $existing;
                }
            } else {
                throw $e;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ حركة الحضور للطالب بنجاح.',
            'attendance' => $attendance,
            'data' => $attendance,
        ]);
    }

    /**
     * 4. تسجيل الانصراف الميداني (جماعي وفردي)
     */
    public function batchDeparture(Request $request): JsonResponse
    {
        $request->validate([
            'date'    => 'required|date',
            'records' => 'required|array',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.departure_status' => 'required|string',
        ]);

        $user = Auth::user();
        $dateStr = $request->input('date');
        $currentTime = Carbon::now()->format('H:i:s');
        $records = $request->input('records');
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($records as $item) {
                $studentId = $item['student_id'];
                $departureStatus = $item['departure_status'];
                $checkOutTime = $item['check_out_time'] ?? $currentTime;
                $departureReason = $item['departure_reason'] ?? null;

                $att = StudentAttendance::where('student_id', $studentId)
                    ->forDate($dateStr)
                    ->first();

                if ($att) {
                    $att->update([
                        'departure_status' => $departureStatus,
                        'check_out_time'   => $checkOutTime,
                        'departure_reason' => $departureReason,
                        'updated_by'       => $user?->id,
                    ]);
                    $updatedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "تم تسجيل انصراف لعدد ({$updatedCount}) طالب بنجاح.",
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'تعذر تسجيل الانصراف: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 5. سجل حضور الطالب الفردي الشامل (History & Stats) داخل ملف الطالب
     */
    public function getStudentAttendanceHistory(Request $request, Student $student): JsonResponse
    {
        $student->load(['branch', 'currentStudyYear', 'department', 'enrolledAcademicYear']);

        $query = StudentAttendance::where('student_id', $student->id)
            ->with(['recorder:id,name', 'updater:id,name'])
            ->orderBy('record_date', 'desc');

        if ($request->has('from_date') && $request->get('from_date')) {
            $query->where('record_date', '>=', $request->get('from_date'));
        }
        if ($request->has('to_date') && $request->get('to_date')) {
            $query->where('record_date', '<=', $request->get('to_date'));
        }
        if ($request->has('academic_year_id') && $request->get('academic_year_id')) {
            $query->where('academic_year_id', $request->get('academic_year_id'));
        }

        $allRecords = $query->get();

        $totalDays = $allRecords->count();
        $presentDays = $allRecords->where('status', 'PRESENT')->count();
        $lateDays = $allRecords->where('status', 'LATE')->count();
        $absentUnexcused = $allRecords->whereIn('status', ['ABSENT', 'ABSENT_UNEXCUSED'])->count();
        $absentExcused = $allRecords->whereIn('status', ['EXCUSED', 'ABSENT_EXCUSED'])->count();
        $earlyDepartures = $allRecords->whereIn('departure_status', ['EARLY_DEPARTURE'])->count();

        $attendanceRate = $totalDays > 0 ? round((($presentDays + $lateDays) / $totalDays) * 100, 1) : 100.0;
        $absenceRate = $totalDays > 0 ? round((($absentUnexcused + $absentExcused) / $totalDays) * 100, 1) : 0.0;

        // إشعارات الغياب الصادرة
        $warnings = AttendanceWarningNotice::where('student_id', $student->id)
            ->orderBy('notice_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'student' => [
                'id'               => $student->id,
                'full_name'        => $student->full_name,
                'academic_number'  => $student->academic_number,
                'national_id'      => $student->national_id,
                'branch_name'      => $student->branch?->name,
                'stage_name'       => $student->currentStudyYear?->name,
                'section_name'     => $student->department?->name,
                'guardian_phone'   => $student->guardian_phone,
            ],
            'statistics' => [
                'total_recorded_days'    => $totalDays,
                'present_days'           => $presentDays,
                'absent_unexcused_days'  => $absentUnexcused,
                'absent_excused_days'    => $absentExcused,
                'total_absent_days'      => $absentUnexcused + $absentExcused,
                'late_days'              => $lateDays,
                'early_departure_days'   => $earlyDepartures,
                'attendance_rate'        => $attendanceRate,
                'absence_rate'           => $absenceRate,
            ],
            'records'  => $allRecords,
            'warnings' => $warnings,
            'data'     => [
                'statistics' => [
                    'total_recorded_days'    => $totalDays,
                    'present_days'           => $presentDays,
                    'absent_unexcused_days'  => $absentUnexcused,
                    'absent_excused_days'    => $absentExcused,
                    'total_absent_days'      => $absentUnexcused + $absentExcused,
                    'late_days'              => $lateDays,
                    'early_departure_days'   => $earlyDepartures,
                    'attendance_rate'        => $attendanceRate,
                    'absence_rate'           => $absenceRate,
                ],
                'records'  => $allRecords,
                'warnings' => $warnings,
            ]
        ]);
    }

    /**
     * 6. لوحة الإحصائيات المركزية ومقارنة الفروع والمراحل
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        $dateStr = $request->get('date', Carbon::today()->format('Y-m-d'));
        $branchId = $request->get('branch_id');

        $totalActiveStudentsQuery = Student::where(function($q) {
            $q->where('academic_status', 'ENROLLED_ACTIVE')
              ->orWhere('academic_status', 'ACTIVE')
              ->orWhereNull('academic_status');
        });
        if ($branchId) {
            $totalActiveStudentsQuery->where('branch_id', $branchId);
        }
        $totalStudents = $totalActiveStudentsQuery->count();

        // سجلات اليوم
        $todayAttendanceQuery = StudentAttendance::whereDate('record_date', $dateStr);
        if ($branchId) {
            $todayAttendanceQuery->where('branch_id', $branchId);
        }
        $todayRecords = $todayAttendanceQuery->get();

        $presentCount = $todayRecords->where('status', 'PRESENT')->count();
        $lateCount = $todayRecords->where('status', 'LATE')->count();
        $absentUnexcused = $todayRecords->whereIn('status', ['ABSENT', 'ABSENT_UNEXCUSED'])->count();
        $absentExcused = $todayRecords->whereIn('status', ['EXCUSED', 'ABSENT_EXCUSED'])->count();
        $earlyDeparture = $todayRecords->where('departure_status', 'EARLY_DEPARTURE')->count();
        $notDepartedCount = $todayRecords->whereIn('status', ['PRESENT', 'LATE'])->where('departure_status', 'NOT_DEPARTED')->count();

        $totalAttendanceToday = $presentCount + $lateCount;
        $totalAbsenceToday = $absentUnexcused + $absentExcused;
        $attendanceRate = $totalStudents > 0 ? round(($totalAttendanceToday / $totalStudents) * 100, 1) : 0;
        $absenceRate = $totalStudents > 0 ? round(($totalAbsenceToday / $totalStudents) * 100, 1) : 0;

        // مقارنة الحضور بين الفروع
        $branches = Branch::where('is_active', true)->get();
        $branchStats = [];
        foreach ($branches as $b) {
            $bStudentsCount = Student::where('branch_id', $b->id)->where('status', 'ACTIVE')->count();
            $bRecords = StudentAttendance::where('branch_id', $b->id)->where('record_date', $dateStr)->get();
            $bPresent = $bRecords->whereIn('status', ['PRESENT', 'LATE'])->count();
            $bAbsent = $bRecords->whereIn('status', ['ABSENT', 'ABSENT_UNEXCUSED', 'EXCUSED', 'ABSENT_EXCUSED'])->count();
            $bRate = $bStudentsCount > 0 ? round(($bPresent / $bStudentsCount) * 100, 1) : 0;

            $branchStats[] = [
                'branch_id'       => $b->id,
                'branch_name'     => $b->name,
                'total_students'  => $bStudentsCount,
                'present_count'   => $bPresent,
                'absent_count'    => $bAbsent,
                'attendance_rate' => $bRate,
            ];
        }

        // أكثر 5 طلاب غياباً هذا الشهر للتنبيه السريع
        $startOfMonth = Carbon::parse($dateStr)->startOfMonth()->format('Y-m-d');
        $atRiskStudents = StudentAttendance::where('record_date', '>=', $startOfMonth)
            ->whereIn('status', ['ABSENT', 'ABSENT_UNEXCUSED'])
            ->select('student_id', DB::raw('count(*) as unexcused_count'))
            ->groupBy('student_id')
            ->having('unexcused_count', '>=', 2)
            ->orderByDesc('unexcused_count')
            ->limit(8)
            ->with(['student.branch', 'student.currentStudyYear'])
            ->get()
            ->map(fn($item) => [
                'student_id'       => $item->student_id,
                'full_name'        => $item->student?->full_name ?? '—',
                'academic_number'  => $item->student?->academic_number ?? '—',
                'branch_name'      => $item->student?->branch?->name ?? '—',
                'stage_name'       => $item->student?->currentStudyYear?->name ?? '—',
                'unexcused_count'  => $item->unexcused_count,
            ]);

        return response()->json([
            'success' => true,
            'stats' => [
                'date'                  => $dateStr,
                'total_students'        => $totalStudents,
                'present_count'         => $presentCount,
                'late_count'            => $lateCount,
                'total_present'         => $totalAttendanceToday,
                'absent_unexcused'      => $absentUnexcused,
                'absent_excused'        => $absentExcused,
                'total_absent'          => $totalAbsenceToday,
                'early_departure'       => $earlyDeparture,
                'not_departed_count'    => $notDepartedCount,
                'attendance_rate'       => $attendanceRate,
                'absence_rate'          => $absenceRate,
            ],
            'branch_comparison' => $branchStats,
            'at_risk_preview'   => $atRiskStudents,
            'data' => [
                'stats' => [
                    'date'                  => $dateStr,
                    'total_students'        => $totalStudents,
                    'present_count'         => $presentCount,
                    'late_count'            => $lateCount,
                    'total_present'         => $totalAttendanceToday,
                    'absent_unexcused'      => $absentUnexcused,
                    'absent_excused'        => $absentExcused,
                    'total_absent'          => $totalAbsenceToday,
                    'early_departure'       => $earlyDeparture,
                    'not_departed_count'    => $notDepartedCount,
                    'attendance_rate'       => $attendanceRate,
                    'absence_rate'          => $absenceRate,
                ],
                'branch_comparison' => $branchStats,
                'at_risk_preview'   => $atRiskStudents,
            ]
        ]);
    }

    /**
     * 7. قائمة الطلاب كثيري الغياب والمتجاوزين للنصاب المعتمد (At-Risk Students Hub - Option B)
     */
    public function getAtRiskStudents(Request $request, AbsenceCalculationEngineService $absenceEngine): JsonResponse
    {
        $minDays = (int)$request->get('min_absent_days', 3);
        $branchId = $request->get('branch_id');
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && !empty($user->branch_id)) {
            $branchId = $user->branch_id;
        }

        $academicYearId = $request->get('academic_year_id');

        $query = StudentAttendance::query()
            ->select(
                'student_id',
                DB::raw('SUM(CASE WHEN status IN ("ABSENT", "ABSENT_UNEXCUSED") THEN 1 ELSE 0 END) as unexcused_days'),
                DB::raw('SUM(CASE WHEN status IN ("EXCUSED", "ABSENT_EXCUSED") THEN 1 ELSE 0 END) as excused_days'),
                DB::raw('SUM(CASE WHEN status = "LATE" THEN 1 ELSE 0 END) as late_days'),
                DB::raw('COUNT(*) as total_recorded_days')
            )
            ->groupBy('student_id')
            ->having('unexcused_days', '>=', $minDays);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $aggregated = $query->orderByDesc('unexcused_days')->get();

        $studentsList = [];
        foreach ($aggregated as $row) {
            $student = Student::with(['branch', 'currentStudyYear', 'department'])->find($row->student_id);
            if (!$student) continue;

            $metrics = $absenceEngine->evaluateStudentAbsence($student, $academicYearId);
            $totalAbsence = (int)$row->unexcused_days + (int)$row->excused_days;
            $unexcused = (int)$row->unexcused_days;

            // تحديد مستوى الإنذار المعتمد بناءً على النسبة الكلية للفصل (Option B)
            $suggestedLevel = $metrics['warning_level'] ?? 'FIRST_WARNING';
            $levelText = match($suggestedLevel) {
                'EXPULSION_NOTICE' => 'قرار حرمان وشطب رسمي (20% فأكثر)',
                'FINAL_WARNING'    => 'إنذار نهائي / خطر الحرمان من الامتحان (15%)',
                'SECOND_WARNING'   => 'إنذار غياب ثانٍ (10%)',
                'FIRST_WARNING'    => 'تنبيه غياب أول (5%)',
                default            => 'متابع أكاديمياً',
            };
            $badgeColor = match($suggestedLevel) {
                'EXPULSION_NOTICE', 'FINAL_WARNING' => 'rose',
                'SECOND_WARNING'                    => 'amber',
                default                             => 'blue',
            };

            // آخر إنذار تم إصداره بالفعل
            $latestNotice = AttendanceWarningNotice::where('student_id', $student->id)->latest()->first();

            $studentsList[] = [
                'student_id'         => $student->id,
                'full_name'          => $student->full_name,
                'academic_number'    => $student->academic_number ?: ('(' . $student->id . ')'),
                'national_id'        => $student->national_id,
                'phone'              => $student->phone,
                'guardian_phone'     => $student->guardian_phone,
                'branch_name'        => $student->branch?->name ?: '—',
                'stage_name'         => $student->currentStudyYear?->name ?: '—',
                'section_name'       => $student->department?->name ?: '—',
                'unexcused_days'     => $unexcused,
                'excused_days'       => (int)$row->excused_days,
                'total_absence'      => $totalAbsence,
                'late_days'          => (int)$row->late_days,
                'total_semester_days'=> $metrics['total_semester_days'],
                'absence_percentage' => $metrics['absence_percentage'],
                'is_deprived'        => $metrics['is_deprived'],
                'suggested_level'    => $suggestedLevel,
                'level_text'         => $levelText,
                'badge_color'        => $badgeColor,
                'last_notice'        => $latestNotice ? [
                    'notice_number'  => $latestNotice->notice_number,
                    'warning_level'  => $latestNotice->warning_level_label,
                    'notice_date'    => $latestNotice->notice_date->format('Y-m-d'),
                ] : null,
            ];
        }

        return response()->json([
            'success' => true,
            'threshold' => $minDays,
            'total_at_risk' => count($studentsList),
            'students' => $studentsList,
            'data' => $studentsList,
        ]);
    }

    /**
     * 8. إصدار وطباعة «إنذار غياب رسمي معتمد» للطالب وولي الأمر (Option B)
     */
    public function issueWarningNotice(Request $request, Student $student, AbsenceCalculationEngineService $absenceEngine): JsonResponse
    {
        $request->validate([
            'warning_level'   => 'required|in:FIRST_WARNING,SECOND_WARNING,FINAL_WARNING,EXPULSION_NOTICE',
            'unexcused_days'  => 'required|integer',
        ]);

        $user = Auth::user();
        $student->load(['branch', 'currentStudyYear', 'department', 'enrolledAcademicYear']);

        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYearName = $student->enrolledAcademicYear?->name ?? ($currentYear?->name ?? '2026/2027');

        $level = $request->input('warning_level');
        $unexcusedDays = (int)$request->input('unexcused_days');
        $totalAbsence = (int)$request->input('total_absence', $unexcusedDays);

        $metrics = $absenceEngine->evaluateStudentAbsence($student, $student->enrolled_academic_year_id ?: $currentYear?->id);
        $totalSemesterDays = $metrics['total_semester_days'];
        $absencePercentage = $metrics['absence_percentage'];

        $noticeNumber = 'إنذار-غياب-' . date('Y') . '-' . str_pad($student->id, 5, '0', STR_PAD_LEFT) . '-' . rand(10, 99);

        $levelTitles = [
            'FIRST_WARNING'     => 'إنذار بالغياب الأول',
            'SECOND_WARNING'    => 'إنذار بالغياب الثاني',
            'FINAL_WARNING'     => 'إنذار بالغياب النهائي (خطر الحرمان)',
            'EXPULSION_NOTICE'  => 'إشعار بالحرمان والشطب لتجاوز نصاب الغياب',
        ];

        $levelTitle = $levelTitles[$level] ?? 'إنذار غياب رسمي';

        $branchName = $student->branch?->name ?: 'الفرع الرئيسي';
        $stageName = $student->currentStudyYear?->name ?: 'السنة الأولى';
        $sectionName = $student->department?->name ?: 'شعبة الدراسات الإسلامية';
        $academicNumber = $student->academic_number ?: ('قيد الاعتماد (' . $student->id . ')');

        // نص الإنذار الرسمي المعتمد بموجب الخيار (ب)
        $statement = ($level === 'EXPULSION_NOTICE')
            ? "قرار حرمان رسمي: تجاوز الطالب ({$student->full_name})، ورقم قيده الأكاديمي ({$academicNumber})، المقيد بالمرحلة الدراسية ({$stageName}) بشعبة ({$sectionName})، نسبة الغياب المسموح بها في الفصل الدراسي وبلغ مجموع غيابه بدون عذر ({$unexcusedDays}) أيام دراسية من أصل ({$totalSemesterDays}) يوماً بنسبة ({$absencePercentage}%). وعليه يعتبر الطالب محروماً رسمياً من دخول الامتحانات."
            : "نحيطكم علماً بأن الطالب: ({$student->full_name})، ورقم قيده الأكاديمي ({$academicNumber})، المقيد بالمرحلة الدراسية ({$stageName}) بشعبة ({$sectionName})، قد تجاوز مدة الغياب بدون عذر مقبول وبلغ مجموع غيابه بدون عذر ({$unexcusedDays}) أيام دراسية من أصل ({$totalSemesterDays}) يوماً بنسبة ({$absencePercentage}%) عن الفصل الدراسي ({$academicYearName}). وعليه نوجه إليكم هذا ({$levelTitle}) للتنبيه والتأكيد على ضرورة الالتزام بالحضور اليومي تفادياً لتطبيق الإجراءات واللوائح والضوابط المنصوص عليها بشأن الحرمان من الامتحانات.";

        $notice = DB::transaction(function() use ($student, $currentYear, $noticeNumber, $level, $unexcusedDays, $totalAbsence, $absencePercentage, $statement, $user, $levelTitle) {
            $createdNotice = AttendanceWarningNotice::create([
                'student_id'            => $student->id,
                'branch_id'             => $student->branch_id,
                'academic_year_id'      => $student->enrolled_academic_year_id ?: $currentYear?->id,
                'notice_number'         => $noticeNumber,
                'warning_level'         => $level,
                'unexcused_days_count'  => $unexcusedDays,
                'total_absence_days'    => $totalAbsence,
                'absence_percentage'    => $absencePercentage,
                'notice_date'           => Carbon::today(),
                'admin_statement'       => $statement,
                'delivery_status'       => 'DELIVERED_TO_GUARDIAN',
                'issued_by'             => $user?->id,
            ]);

            // Enforce retroactive edit lock on absent records
            StudentAttendance::where('student_id', $student->id)
                ->whereIn('status', ['absent', 'ABSENT', 'ABSENT_UNEXCUSED'])
                ->whereNull('modification_reason')
                ->update([
                    'modification_reason' => "مغلق آلياً بموجب إشعار الحرمان {$noticeNumber} — يمنع التعديل الرجعي إلا باعتماد مركزي",
                ]);

            // التدقيق الأمني بسلسلة التشفير
            SystemAuditTrail::log(
                eventType: 'ATTENDANCE_WARNING_ISSUED',
                description: "قام المستخدم " . ($user?->name ?? 'إدارة المعهد') . " بإصدار ({$levelTitle}) للطالب: {$student->full_name} بنسبة غياب ({$absencePercentage}%).",
                payload: [
                    'notice_number'  => $noticeNumber,
                    'student_id'     => $student->id,
                    'warning_level'  => $level,
                    'unexcused_days' => $unexcusedDays,
                    'percentage'     => $absencePercentage,
                ],
                userId: $user?->id,
                branchId: $student->branch_id,
                modelType: AttendanceWarningNotice::class,
                modelId: $createdNotice->id,
                severity: $level === 'EXPULSION_NOTICE' ? 'CRITICAL' : 'WARNING'
            );

            return $createdNotice;
        });

        $profile = \App\Services\AdminSettingsService::getInstituteProfile();
        $signatories = \App\Services\AdminSettingsService::getSignatoriesFor('warning_notice', $student->branch_id);

        $payload = [
            'institute_name'    => $profile['institute_name'],
            'supervising_body'  => $profile['supervising_body'],
            'department_body'   => $profile['supervising_department'],
            'logo_url'          => $profile['logo_url'],
            'stamp_url'         => $profile['stamp_url'],
            'notice_title'      => $levelTitle,
            'notice_number'     => $noticeNumber,
            'issued_date'       => Carbon::now()->format('Y/m/d'),
            'student'           => [
                'id'                => $student->id,
                'full_name'         => $student->full_name,
                'academic_number'   => $academicNumber,
                'national_id'       => $student->national_id,
                'branch_name'       => $branchName,
                'stage_name'        => $stageName,
                'section_name'      => $sectionName,
                'guardian_name'     => $student->guardian_name ?: ($student->father_name . ' ' . $student->family_name),
                'guardian_phone'    => $student->guardian_phone ?: '—',
                'academic_year'     => $academicYearName,
                'unexcused_days'    => $unexcusedDays,
                'total_absence'     => $totalAbsence,
            ],
            'official_statement'=> $statement,
            'signatories'       => [
                'officer_name'      => $signatories['prepared_by']['name'] ?? ($user?->name ?? 'مسؤول شؤون الطلاب والحضور'),
                'officer_role'      => $signatories['prepared_by']['title'] ?? ($user?->role ?? 'مشرف الحضور والانضباط'),
                'director_name'     => $signatories['approved_by']['title'] ?? 'مدير فرع المعهد',
            ],
            'qr_verification'   => url("/api/v1/students/{$student->id}/card"),
        ];

        return response()->json([
            'success' => true,
            'message' => "تم إصدار {$levelTitle} بنجاح.",
            'notice'  => $payload,
            'data'    => $payload,
        ]);
    }

    /**
     * 9. تقارير وكشوفات الحضور الرسمية المعتمدة للطباعة (Official Print Engine)
     */
    public function getOfficialAttendanceReport(Request $request): JsonResponse
    {
        $reportType = $request->get('report_type', 'DAILY_SHEET'); // DAILY_SHEET, PERIOD_ABSENCE, LATE_SHEET, CHRONIC_ABSENCE
        $dateStr = $request->get('date', Carbon::today()->format('Y-m-d'));
        $fromDate = $request->get('from_date', $dateStr);
        $toDate = $request->get('to_date', $dateStr);
        $branchId = $request->get('branch_id');
        $studyYearId = $request->get('study_year_id');
        $departmentId = $request->get('department_id');

        $user = Auth::user();
        $currentYear = AcademicYear::where('is_current', true)->first();

        $branch = $branchId ? Branch::find($branchId) : null;
        $studyYear = $studyYearId ? StudyYear::find($studyYearId) : null;
        $department = $departmentId ? Department::find($departmentId) : null;

        $rows = [];
        $reportTitle = 'كشف حضور وانصراف الطلاب المعتمد';

        if ($reportType === 'DAILY_SHEET') {
            $reportTitle = 'كشف الحضور والغياب اليومي للطلاب';
            $sheetRes = $this->getDailySheet($request);
            $sheetData = $sheetRes->getData(true);
            $rows = $sheetData['students'] ?? [];
            $summary = $sheetData['summary'] ?? [];
        } elseif ($reportType === 'LATE_SHEET') {
            $reportTitle = 'كشف الطلاب المتأخرين والمنصرفين مبكراً';
            $query = StudentAttendance::where('record_date', $dateStr)
                ->where(function($q) {
                    $q->where('status', 'LATE')->orWhere('departure_status', 'EARLY_DEPARTURE');
                })
                ->with(['student.branch', 'student.currentStudyYear', 'student.department']);

            if ($branchId) $query->where('branch_id', $branchId);
            $records = $query->get();

            foreach ($records as $r) {
                $rows[] = [
                    'student_id'       => $r->student_id,
                    'full_name'        => $r->student?->full_name ?? '—',
                    'academic_number'  => $r->student?->academic_number ?? '—',
                    'national_id'      => $r->student?->national_id ?? '—',
                    'branch_name'      => $r->student?->branch?->name ?? '—',
                    'stage_name'       => $r->student?->currentStudyYear?->name ?? '—',
                    'status_label'     => $r->status_label,
                    'late_minutes'     => $r->late_minutes,
                    'departure_status' => $r->departure_status_label,
                    'check_in_time'    => $r->check_in_time ?: '—',
                    'check_out_time'   => $r->check_out_time ?: '—',
                    'departure_reason' => $r->departure_reason ?: '—',
                ];
            }
            $summary = ['total_count' => count($rows)];
        } else { // CHRONIC_ABSENCE or PERIOD_ABSENCE
            $reportTitle = 'كشف الطلاب الأكثر غياباً والمعرضين لإنذارات الحرمان';
            $atRiskRes = $this->getAtRiskStudents($request);
            $atRiskData = $atRiskRes->getData(true);
            $rows = $atRiskData['students'] ?? [];
            $summary = ['total_at_risk' => count($rows)];
        }

        $profile = \App\Services\AdminSettingsService::getInstituteProfile();
        $signatories = \App\Services\AdminSettingsService::getSignatoriesFor('attendance_sheet', $branchId);

        $payload = [
            'institute_name'    => $profile['institute_name'],
            'supervising_body'  => $profile['supervising_body'],
            'department_body'   => $profile['supervising_department'],
            'logo_url'          => $profile['logo_url'],
            'stamp_url'         => $profile['stamp_url'],
            'report_title'      => $reportTitle,
            'report_type'       => $reportType,
            'ref_number'        => 'تقرير-حضور-' . date('Ymd-His'),
            'printed_at'        => Carbon::now()->format('Y/m/d H:i'),
            'printed_by'        => $user?->name ?? 'مسؤول النظام الأكاديمي',
            'academic_year'     => $currentYear?->name ?? '2026/2027',
            'branch_name'       => $branch?->name ?? 'جميع الفروع',
            'stage_name'        => $studyYear?->name ?? 'جميع المراحل',
            'section_name'      => $department?->name ?? 'جميع الشعب',
            'date_info'         => [
                'target_date'   => $dateStr,
                'from_date'     => $fromDate,
                'to_date'       => $toDate,
            ],
            'summary'           => $summary ?? [],
            'rows'              => $rows,
            'columns'           => [
                ['key' => 'academic_number', 'label' => 'رقم القيد', 'visible' => true],
                ['key' => 'full_name', 'label' => 'اسم الطالب الرباعي', 'visible' => true],
                ['key' => 'branch_name', 'label' => 'الفرع', 'visible' => true],
                ['key' => 'stage_name', 'label' => 'المرحلة', 'visible' => true],
                ['key' => 'status', 'label' => 'حالة الحضور', 'visible' => true],
                ['key' => 'late_minutes', 'label' => 'التأخير (دقيقة)', 'visible' => true],
                ['key' => 'check_out_time', 'label' => 'الانصراف', 'visible' => true],
                ['key' => 'departure_reason', 'label' => 'ملاحظات / سبب الإذن', 'visible' => true],
            ],
            'signatories'       => [
                'registrar_name' => $signatories['prepared_by']['title'] ?? 'مسؤول شؤون الطلاب',
                'dean_name'      => $signatories['approved_by']['title'] ?? 'مدير فرع المعهد / الاعتماد الأكاديمي',
            ],
        ];

        return response()->json([
            'success' => true,
            'report'  => $payload,
            'data'    => $payload,
        ]);
    }

    /**
     * 10. مسح رمز QR لبطاقة الطالب أو الباركود وتسجيل الحضور / الانصراف الذكي
     */
    public function scanQrOrBarcode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'action' => 'nullable|string|in:CHECK_IN,CHECK_OUT,AUTO',
            'branch_id' => 'nullable|integer',
            'verification_method' => 'nullable|string',
        ]);

        $rawCode = trim($request->input('code'));
        $action = $request->input('action', 'AUTO');
        $branchId = $request->input('branch_id');
        $method = $request->input('verification_method', 'QR_CARD');

        // استخراج وتحديد الطالب من الكود
        $student = null;
        if (preg_match('/IIIS:STU:([^:]+):NID:([^:]+)/i', $rawCode, $matches)) {
            $academicNumber = trim($matches[1]);
            $nationalId = trim($matches[2]);
            $student = Student::where('academic_number', $academicNumber)
                ->orWhere('national_id', $nationalId)
                ->first();
        } elseif (preg_match('/IIIS-STU-(.+)/i', $rawCode, $matches)) {
            $codeVal = trim($matches[1]);
            $student = Student::where('academic_number', $codeVal)
                ->orWhere('national_id', $codeVal)
                ->orWhere('id', is_numeric($codeVal) ? (int)$codeVal : 0)
                ->first();
        } else {
            $student = Student::where('academic_number', $rawCode)
                ->orWhere('national_id', $rawCode)
                ->orWhere('ministry_student_id', $rawCode)
                ->orWhere('id', is_numeric($rawCode) ? (int)$rawCode : 0)
                ->first();
        }

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم التعرف على رمز الطالب أو أن بطاقة الطالب غير مسجلة بالنظام: ' . $rawCode,
            ], 404);
        }

        $todayStr = Carbon::today()->format('Y-m-d');
        $nowTime = Carbon::now()->format('H:i');
        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYearId = $currentYear?->id ?? 1;

        $attendance = StudentAttendance::where('student_id', $student->id)
            ->forDate($todayStr)
            ->first();

        // في حال النمط التلقائي: إذا لم يسجل دخول اليوم يسجل حضور، وإذا مسجل حضور يسجل انصراف
        if ($action === 'AUTO') {
            if (!$attendance || in_array($attendance->status, ['NOT_SET', 'ABSENT', 'ABSENT_UNEXCUSED', 'EXCUSED'])) {
                $action = 'CHECK_IN';
            } else {
                $action = 'CHECK_OUT';
            }
        }

        if ($action === 'CHECK_IN') {
            $alreadyIn = ($attendance && in_array($attendance->status, ['PRESENT', 'LATE']));

            // احتساب التأخير إذا كان الدخول بعد 08:15 صباحاً
            $officialStart = Carbon::today()->setTime(8, 15);
            $lateMinutes = 0;
            $status = 'PRESENT';
            if (Carbon::now()->greaterThan($officialStart)) {
                $lateMinutes = Carbon::now()->diffInMinutes($officialStart);
                if ($lateMinutes > 5) {
                    $status = 'LATE';
                }
            }

            if (!$attendance) {
                $attendance = new StudentAttendance();
                $attendance->student_id = $student->id;
                $attendance->branch_id = $student->branch_id ?? $branchId;
                $attendance->academic_year_id = $academicYearId;
                $attendance->study_year_id = $student->current_study_year_id;
                $attendance->department_id = $student->department_id;
                $attendance->record_date = $todayStr;
                $attendance->day_of_week = $this->arabicDays[Carbon::today()->dayOfWeek] ?? 'اليوم';
                $attendance->recorded_by = Auth::id() ?? 1;
            }

            $attendance->status = $status;
            $attendance->check_in_time = $nowTime;
            $attendance->late_minutes = $lateMinutes;
            $attendance->verification_method = $method;
            if ($attendance->departure_status === 'DEPARTED') {
                $attendance->departure_status = 'NOT_DEPARTED';
            }

            try {
                $attendance->save();
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'UNIQUE')) {
                    $existing = StudentAttendance::where('student_id', $student->id)->forDate($todayStr)->first();
                    if ($existing) {
                        $existing->status = $status;
                        $existing->check_in_time = $nowTime;
                        $existing->late_minutes = $lateMinutes;
                        $existing->verification_method = $method;
                        if ($existing->departure_status === 'DEPARTED') {
                            $existing->departure_status = 'NOT_DEPARTED';
                        }
                        $existing->save();
                        $attendance = $existing;
                    }
                } else {
                    throw $e;
                }
            }

            $msg = $alreadyIn 
                ? 'تم تحديث توقيت دخول الطالب المسجل مسبقاً' 
                : ($status === 'LATE' ? "تم تسجيل حضور الطالب (متأخر $lateMinutes دقيقة)" : 'تم تسجيل حضور الطالب في الموعد المحدد');

            return response()->json([
                'success' => true,
                'action' => 'CHECK_IN',
                'is_duplicate' => $alreadyIn,
                'message' => $msg,
                'data' => [
                    'student_id' => $student->id,
                    'full_name' => $student->full_name,
                    'academic_number' => $student->academic_number ?: ('#' . $student->id),
                    'national_id' => $student->national_id,
                    'branch_name' => $student->branch?->name ?: 'الفرع الرئيسي',
                    'stage_name' => $student->currentStudyYear?->name ?: '—',
                    'section_name' => $student->department?->name ?: '—',
                    'photo_url' => $student->profile_photo_path ? asset('storage/' . $student->profile_photo_path) : null,
                    'status' => $attendance->status,
                    'status_label' => $attendance->status_label,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'departure_status' => $attendance->departure_status,
                    'late_minutes' => $attendance->late_minutes,
                    'verification_method' => $attendance->verification_method,
                    'timestamp' => Carbon::now()->format('Y-m-d H:i:s'),
                ]
            ]);
        } else {
            // CHECK_OUT
            if (!$attendance) {
                $attendance = new StudentAttendance();
                $attendance->student_id = $student->id;
                $attendance->branch_id = $student->branch_id ?? $branchId;
                $attendance->academic_year_id = $academicYearId;
                $attendance->study_year_id = $student->current_study_year_id;
                $attendance->department_id = $student->department_id;
                $attendance->record_date = $todayStr;
                $attendance->day_of_week = $this->arabicDays[Carbon::today()->dayOfWeek] ?? 'اليوم';
                $attendance->status = 'PRESENT';
                $attendance->check_in_time = '08:00';
                $attendance->recorded_by = Auth::id() ?? 1;
            }

            $attendance->check_out_time = $nowTime;
            $attendance->departure_status = 'DEPARTED';
            $attendance->verification_method = $method;

            try {
                $attendance->save();
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'UNIQUE')) {
                    $existing = StudentAttendance::where('student_id', $student->id)->forDate($todayStr)->first();
                    if ($existing) {
                        $existing->check_out_time = $nowTime;
                        $existing->departure_status = 'DEPARTED';
                        $existing->verification_method = $method;
                        $existing->save();
                        $attendance = $existing;
                    }
                } else {
                    throw $e;
                }
            }

            return response()->json([
                'success' => true,
                'action' => 'CHECK_OUT',
                'message' => 'تم تسجيل انصراف الطالب بنجاح في الوقت: ' . $nowTime,
                'data' => [
                    'student_id' => $student->id,
                    'full_name' => $student->full_name,
                    'academic_number' => $student->academic_number ?: ('#' . $student->id),
                    'national_id' => $student->national_id,
                    'branch_name' => $student->branch?->name ?: 'الفرع الرئيسي',
                    'stage_name' => $student->currentStudyYear?->name ?: '—',
                    'section_name' => $student->department?->name ?: '—',
                    'photo_url' => $student->profile_photo_path ? asset('storage/' . $student->profile_photo_path) : null,
                    'status' => $attendance->status,
                    'status_label' => $attendance->status_label,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'departure_status' => $attendance->departure_status,
                    'verification_method' => $attendance->verification_method,
                    'timestamp' => Carbon::now()->format('Y-m-d H:i:s'),
                ]
            ]);
        }
    }

    /**
     * 11. إصدار واعتماد إذن انصراف وخروج مبكر رسمي للطالب مع بيانات الطباعة
     */
    public function issueEarlyDeparturePermission(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'exit_time' => 'nullable|string',
            'reason' => 'required|string|max:200',
            'guardian_name' => 'required|string|max:150',
            'guardian_phone' => 'nullable|string|max:50',
            'authorized_by' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
        ]);

        $dateStr = Carbon::parse($validated['date'] ?? Carbon::today())->format('Y-m-d');
        $exitTime = $validated['exit_time'] ?? Carbon::now()->format('H:i');
        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYearId = $currentYear?->id ?? 1;

        $attendance = StudentAttendance::where('student_id', $student->id)
            ->forDate($dateStr)
            ->first();

        if (!$attendance) {
            $attendance = new StudentAttendance();
            $attendance->student_id = $student->id;
            $attendance->branch_id = $student->branch_id;
            $attendance->academic_year_id = $academicYearId;
            $attendance->study_year_id = $student->current_study_year_id;
            $attendance->department_id = $student->department_id;
            $attendance->record_date = $dateStr;
            $attendance->day_of_week = $this->arabicDays[Carbon::parse($dateStr)->dayOfWeek] ?? 'اليوم';
            $attendance->status = 'PRESENT';
            $attendance->check_in_time = '08:00';
            $attendance->recorded_by = Auth::id() ?? 1;
        }

        // توليد رقم إذن فريد
        $countToday = StudentAttendance::forDate($dateStr)
            ->whereNotNull('early_permission_slip_number')
            ->count() + 1;
        $slipNumber = 'PERM-' . date('Ymd', strtotime($dateStr)) . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

        $authName = $validated['authorized_by'] ?? (Auth::user()?->name ?? 'مشرف شؤون الطلاب العام');

        $attendance->departure_status = 'EARLY_DEPARTURE';
        $attendance->check_out_time = $exitTime;
        $attendance->departure_reason = $validated['reason'];
        $attendance->early_permission_slip_number = $slipNumber;
        $attendance->early_permission_reason = $validated['reason'];
        $attendance->early_permission_guardian_name = $validated['guardian_name'];
        $attendance->early_permission_guardian_phone = $validated['guardian_phone'] ?? $student->guardian_phone;
        $attendance->early_permission_authorized_by = $authName;
        $attendance->early_permission_notes = $validated['notes'] ?? null;
        $attendance->updated_by = Auth::id() ?? 1;

        try {
            $attendance->save();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'UNIQUE')) {
                $existing = StudentAttendance::where('student_id', $student->id)->forDate($dateStr)->first();
                if ($existing) {
                    $existing->departure_status = 'EARLY_DEPARTURE';
                    $existing->check_out_time = $exitTime;
                    $existing->departure_reason = $validated['reason'];
                    $existing->early_permission_slip_number = $slipNumber;
                    $existing->early_permission_reason = $validated['reason'];
                    $existing->early_permission_guardian_name = $validated['guardian_name'];
                    $existing->early_permission_guardian_phone = $validated['guardian_phone'] ?? $student->guardian_phone;
                    $existing->early_permission_authorized_by = $authName;
                    $existing->early_permission_notes = $validated['notes'] ?? null;
                    $existing->updated_by = Auth::id() ?? 1;
                    $existing->save();
                    $attendance = $existing;
                }
            } else {
                throw $e;
            }
        }

        // تسجيل في سجل التدقيق
        try {
            SystemAuditTrail::create([
                'user_id'     => Auth::id() ?? 1,
                'event_type'  => 'EARLY_PERMISSION_ISSUED',
                'description' => "إصدار إذن خروج مبكر رسمي للطالب: {$student->full_name} برقم: {$slipNumber}",
                'ip_address'  => request()->ip(),
                'payload'     => [
                    'slip_number' => $slipNumber,
                    'student_id'  => $student->id,
                    'reason'      => $validated['reason'],
                ],
                'created_at'  => Carbon::now(),
            ]);
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'تم إصدار واعتماد إذن الانصراف المبكر بنجاح',
            'slip' => [
                'slip_number' => $slipNumber,
                'issue_date' => $dateStr,
                'exit_time' => $exitTime,
                'student' => [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'academic_number' => $student->academic_number ?: ('#' . $student->id),
                    'national_id' => $student->national_id,
                    'branch_name' => $student->branch?->name ?: 'الفرع الرئيسي',
                    'stage_name' => $student->currentStudyYear?->name ?: '—',
                    'section_name' => $student->department?->name ?: '—',
                ],
                'reason' => $validated['reason'],
                'guardian_name' => $validated['guardian_name'],
                'guardian_phone' => $validated['guardian_phone'] ?? $student->guardian_phone,
                'authorized_by' => $authName,
                'notes' => $validated['notes'] ?? '',
                'barcode' => $slipNumber,
                'institute_name' => 'المعهد التخصصي للدراسات الإسلامية',
                'management_title' => 'إدارة شؤون الطلاب والانضباط الأكاديمي',
            ]
        ]);
    }

    /**
     * 12. استقبال وتزامن حركات أجهزة البصمة الحيوية الخارجية (Biometric Machine Push Webhook)
     */
    public function syncBiometricLogs(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'logs' => 'required|array',
            'logs.*.user_code' => 'required|string',
            'logs.*.timestamp' => 'required|string',
            'logs.*.type' => 'nullable|string|in:IN,OUT,AUTO',
            'logs.*.log_id' => 'nullable|string',
        ]);

        $deviceId = $request->input('device_id');
        $logs = $request->input('logs');
        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYearId = $currentYear?->id ?? 1;

        $processed = 0;
        $errors = [];

        foreach ($logs as $item) {
            $userCode = trim($item['user_code']);
            $student = Student::where('academic_number', $userCode)
                ->orWhere('national_id', $userCode)
                ->orWhere('id', is_numeric($userCode) ? (int)$userCode : 0)
                ->first();

            if (!$student) {
                $errors[] = "الطالب صاحب المعرف $userCode غير مسجل بالنظام";
                continue;
            }

            $logTime = Carbon::parse($item['timestamp']);
            $recordDate = $logTime->format('Y-m-d');
            $timeStr = $logTime->format('H:i');
            $logType = $item['type'] ?? 'AUTO';

            $attendance = StudentAttendance::where('student_id', $student->id)
                ->forDate($recordDate)
                ->first();

            if (!$attendance) {
                $attendance = new StudentAttendance();
                $attendance->student_id = $student->id;
                $attendance->record_date = $recordDate;
                $attendance->branch_id = $student->branch_id;
                $attendance->academic_year_id = $academicYearId;
                $attendance->study_year_id = $student->current_study_year_id;
                $attendance->department_id = $student->department_id;
                $attendance->day_of_week = $this->arabicDays[$logTime->dayOfWeek] ?? 'اليوم';
                $attendance->status = 'PRESENT';
                $attendance->check_in_time = $timeStr;
                $attendance->verification_method = 'BIOMETRIC_FINGERPRINT';
                $attendance->device_id = $deviceId;
                $attendance->biometric_log_id = $item['log_id'] ?? null;
                $attendance->recorded_by = 1;
            } else {
                if ($logType === 'OUT' || ($logType === 'AUTO' && $attendance->check_in_time && $timeStr > $attendance->check_in_time)) {
                    $attendance->check_out_time = $timeStr;
                    $attendance->departure_status = 'DEPARTED';
                }
                $attendance->device_id = $deviceId;
                $attendance->verification_method = 'BIOMETRIC_FINGERPRINT';
            }

            try {
                $attendance->save();
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'UNIQUE')) {
                    $existing = StudentAttendance::where('student_id', $student->id)->forDate($recordDate)->first();
                    if ($existing) {
                        if ($logType === 'OUT' || ($logType === 'AUTO' && $existing->check_in_time && $timeStr > $existing->check_in_time)) {
                            $existing->check_out_time = $timeStr;
                            $existing->departure_status = 'DEPARTED';
                        }
                        $existing->device_id = $deviceId;
                        $existing->verification_method = 'BIOMETRIC_FINGERPRINT';
                        $existing->save();
                    }
                } else {
                    throw $e;
                }
            }
            $processed++;
        }

        return response()->json([
            'success' => true,
            'message' => "تمت معالجة وتزامن $processed حركة بصمة بنجاح",
            'processed_count' => $processed,
            'errors' => $errors,
        ]);
    }

    /**
     * 13. استقبال حركات الحضور المتراكمة دون اتصال (Offline Batch Sync Engine)
     * مع التحقق من المفتاح الرقمي (Idempotency Nonce) وفض النزاعات الزمنية
     */
    public function batchSync(Request $request, \App\Services\OfflineAttendanceSyncService $syncService): JsonResponse
    {
        $request->validate([
            'records'        => 'required|array',
            'records.*.student_id' => 'required',
            'batch_id'       => 'nullable|string',
            'device_uuid'    => 'nullable|string',
        ]);

        $user = Auth::user();
        if (!$user) {
            // Fallback for authorized token or local user
            $user = \App\Models\User::first();
        }

        try {
            $results = $syncService->processBatch($request->all(), $user);

            return response()->json([
                'success' => true,
                'message' => "تمت معالجة دفعة المزامنة بنجاح: {$results['synced']} جديد/محدث، {$results['duplicates']} مكرر تم تجاهله، {$results['conflicts']} نزاع مسجل، {$results['failed']} إخفاق.",
                'data'    => $results,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشلت معالجة دفعة المزامنة: ' . $e->getMessage(),
            ], 500);
        }
    }
}

