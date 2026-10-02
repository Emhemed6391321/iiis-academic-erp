<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AcademicYearRolloverLog;
use App\Models\OperationalWindow;
use App\Models\OperationalWindowException;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\StudyYear;
use App\Models\Branch;
use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get all calendar and settings data
     */
    public function getCalendarData(Request $request)
    {
        $yearId = $request->query('year_id');

        $academicYears = AcademicYear::orderBy('id', 'desc')->get()->map(function ($y) {
            $y->status_label = $y->status_label;
            return $y;
        });
        $activeYear = null;

        if ($yearId) {
            $activeYear = AcademicYear::find($yearId);
        }
        if (!$activeYear) {
            $activeYear = AcademicYear::where('is_current', true)->first() ?: $academicYears->first();
        }

        $activeYearId = $activeYear ? $activeYear->id : 1;
        $startDate = ($activeYear && $activeYear->start_date) ? (is_string($activeYear->start_date) ? $activeYear->start_date : $activeYear->start_date->format('Y-m-d')) : '2026-09-01';
        $endDate = ($activeYear && $activeYear->end_date) ? (is_string($activeYear->end_date) ? $activeYear->end_date : $activeYear->end_date->format('Y-m-d')) : '2027-06-30';

        // Weekend days
        $weekendsSetting = DB::table('system_central_settings')->where('setting_key', 'weekend_days')->value('setting_value');
        $weekends = $weekendsSetting ? json_decode($weekendsSetting, true) : ['friday', 'saturday'];

        // Calculate KPIs
        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);
        $totalDays = ($endTs >= $startTs) ? (int)(($endTs - $startTs) / 86400) + 1 : 0;

        $weekendCount = 0;
        $weekendMap = [];
        for ($t = $startTs; $t <= $endTs; $t += 86400) {
            $dayName = strtolower(date('l', $t));
            if (in_array($dayName, $weekends)) {
                $weekendCount++;
                $weekendMap[date('Y-m-d', $t)] = true;
            }
        }

        // Calendar events
        $events = DB::table('academic_calendar_events')
            ->where('academic_year_id', $activeYearId)
            ->orderBy('event_date', 'asc')
            ->get();

        $officialHolidays = 0;
        foreach ($events as $ev) {
            if ($ev->is_holiday) {
                $eStart = strtotime($ev->event_date);
                $eEnd = !empty($ev->end_date) ? strtotime($ev->end_date) : $eStart;
                for ($d = $eStart; $d <= $eEnd; $d += 86400) {
                    $dStr = date('Y-m-d', $d);
                    if ($d >= $startTs && $d <= $endTs) {
                        if (!isset($weekendMap[$dStr])) {
                            $officialHolidays++;
                            $weekendMap[$dStr] = true;
                        }
                    }
                }
            }
        }

        $netStudyDays = max(0, $totalDays - $weekendCount - $officialHolidays);
        $studyPercentage = $totalDays > 0 ? round(($netStudyDays / $totalDays) * 100, 1) : 0;

        // Schedules
        $schedules = DB::table('academic_schedules')
            ->where('academic_year_id', $activeYearId)
            ->orderBy('start_date', 'asc')
            ->get();

        // Student services
        $servicesSetting = DB::table('system_central_settings')->where('setting_key', 'student_services_config')->value('setting_value');
        $studentServices = $servicesSetting ? json_decode($servicesSetting, true) : [];

        // Admin periods
        $periodsSetting = DB::table('system_central_settings')->where('setting_key', 'admin_periods_config')->value('setting_value');
        $adminPeriods = $periodsSetting ? json_decode($periodsSetting, true) : [];

        // Results gateways
        $gateTransport = DB::table('system_central_settings')->where('setting_key', 'gate_transport_first_round')->value('setting_value') ?: 'closed';
        $gateSecond = DB::table('system_central_settings')->where('setting_key', 'gate_second_round_all')->value('setting_value') ?: 'closed';
        $gateDiplomaManual = DB::table('system_central_settings')->where('setting_key', 'gate_diploma_first_round_manual')->value('setting_value') ?: 'closed';
        $gateDiplomaAuto = DB::table('system_central_settings')->where('setting_key', 'gate_diploma_first_round_auto')->value('setting_value') ?: '0';
        $diplomaOpenAt = DB::table('system_central_settings')->where('setting_key', 'gate_diploma_open_at')->value('setting_value') ?: '2026-09-20T09:00';
        $diplomaCloseAt = DB::table('system_central_settings')->where('setting_key', 'gate_diploma_close_at')->value('setting_value') ?: '2026-09-30T18:00';

        $nowIso = date('Y-m-d\TH:i');
        $diplomaEffectiveOpen = false;
        if ($gateDiplomaManual === 'open') {
            $diplomaEffectiveOpen = true;
        } elseif ($gateDiplomaAuto === '1') {
            if ($nowIso >= $diplomaOpenAt && $nowIso <= $diplomaCloseAt) {
                $diplomaEffectiveOpen = true;
            }
        }

        // Organizational positions
        $positions = DB::table('organizational_positions as p')
            ->leftJoin('users as u', 'p.user_id', '=', 'u.id')
            ->leftJoin('roles as r', 'u.role_id', '=', 'r.id')
            ->select('p.*', 'u.name as user_name', 'u.email as user_email', 'r.display_name as role_display')
            ->orderBy('p.admin_code', 'asc')
            ->get();

        $users = User::select('id', 'name', 'email')->orderBy('name', 'asc')->get();

        // إحصائيات الأعوام الدراسية
        $yearStats = $activeYear ? $activeYear->getStats() : [];
        $canEdit = $activeYear && !$activeYear->is_locked;

        return response()->json([
            'status' => 'success',
            'data' => [
                'academic_years' => $academicYears,
                'active_year' => $activeYear,
                'year_stats' => $yearStats,
                'can_edit' => $canEdit,
                'kpis' => [
                    'total_days' => $totalDays,
                    'weekend_days' => $weekendCount,
                    'official_holidays' => $officialHolidays,
                    'net_study_days' => $netStudyDays,
                    'study_percentage' => $studyPercentage,
                    'weekends' => $weekends,
                ],
                'calendar_events' => $events,
                'schedules' => $schedules,
                'student_services' => $studentServices,
                'admin_periods' => $adminPeriods,
                'results_gateways' => [
                    'transport_first_round' => $gateTransport,
                    'second_round_all' => $gateSecond,
                    'diploma_manual' => $gateDiplomaManual,
                    'diploma_auto' => $gateDiplomaAuto,
                    'diploma_open_at' => $diplomaOpenAt,
                    'diploma_close_at' => $diplomaCloseAt,
                    'diploma_effective_open' => $diplomaEffectiveOpen,
                ],
                'positions' => $positions,
                'users' => $users,
            ]
        ]);
    }

    // ===================================================================
    // ACADEMIC YEAR ENGINE — إدارة الأعوام الدراسية
    // ===================================================================

    /**
     * إنشاء عام دراسي جديد مع خيار نسخ المقررات من العام السابق
     */
    public function createYear(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code'          => 'required|string|max:20|unique:academic_years,code',
            'name'          => 'required|string|max:100',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
            'copy_courses'  => 'nullable|boolean',
            'source_year_id'=> 'nullable|exists:academic_years,id',
            'notes'         => 'nullable|string|max:500',
        ], [
            'code.unique'   => 'رمز العام الدراسي مستخدم مسبقاً. يرجى اختيار رمز مختلف.',
            'end_date.after'=> 'تاريخ انتهاء العام يجب أن يكون بعد تاريخ البداية.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        return DB::transaction(function () use ($request): JsonResponse {
            // إنشاء العام الجديد كمسودة
            $newYear = AcademicYear::create([
                'code'       => $request->code,
                'name'       => $request->name,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'is_current' => false,
                'is_locked'  => false,
                'status'     => 'DRAFT',
                'notes'      => $request->notes,
            ]);

            $copiedCount = 0;

            // نسخ المقررات من العام المصدر إذا طُلب ذلك
            if ($request->boolean('copy_courses', true)) {
                $sourceYearId = $request->source_year_id;

                // إذا لم يُحدد مصدر، نأخذ العام النشط أو الأحدث
                if (!$sourceYearId) {
                    $sourceYear = AcademicYear::where('is_current', true)->first()
                                ?? AcademicYear::where('id', '!=', $newYear->id)->orderBy('id', 'desc')->first();
                    $sourceYearId = $sourceYear?->id;
                }

                if ($sourceYearId) {
                    $sourceCourses = Course::where('academic_year_id', $sourceYearId)->get();

                    foreach ($sourceCourses as $course) {
                        Course::create([
                            'academic_year_id'     => $newYear->id,
                            'study_year_id'        => $course->study_year_id,
                            'department_id'        => $course->department_id,
                            'semester'             => $course->semester,
                            'code'                 => $course->code,
                            'name'                 => $course->name,
                            'credit_hours'         => $course->credit_hours,
                            'weekly_hours'         => $course->weekly_hours,
                            'assessment_system'    => $course->assessment_system,
                            'max_coursework_grade' => $course->max_coursework_grade,
                            'max_midterm_grade'    => $course->max_midterm_grade,
                            'max_final_grade'      => $course->max_final_grade,
                            'pass_grade'           => $course->pass_grade,
                            'max_score'            => $course->max_score,
                            'pass_min_score'       => $course->pass_min_score,
                            'second_round_max'     => $course->second_round_max,
                            'is_active'            => true,
                        ]);
                        $copiedCount++;
                    }
                }
            }

            return response()->json([
                'status'        => 'success',
                'message'       => "تم إنشاء العام الدراسي «{$newYear->name}» بنجاح كمسودة." . ($copiedCount > 0 ? " تم نسخ {$copiedCount} مقرراً من العام السابق." : ' لم يتم نسخ مقررات.'),
                'year'          => $newYear,
                'copied_courses'=> $copiedCount,
            ], 201);
        });
    }

    /**
     * تفعيل واعتماد عام دراسي جديد — يقفل العام السابق تلقائياً
     */
    public function activateYear(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'year_id' => 'required|exists:academic_years,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $newYear = AcademicYear::findOrFail($request->year_id);

        if ($newYear->is_locked) {
            return response()->json([
                'status'  => 'error',
                'message' => "لا يمكن تفعيل العام «{$newYear->name}» لأنه مقفل نهائياً.",
            ], 422);
        }

        if ($newYear->is_current) {
            return response()->json([
                'status'  => 'warning',
                'message' => "العام «{$newYear->name}» هو العام النشط حالياً بالفعل.",
            ]);
        }

        return DB::transaction(function () use ($newYear, $request): JsonResponse {
            $userId = Auth::id();

            // قفل وإيقاف العام الدراسي السابق النشط
            $previousYear = AcademicYear::where('is_current', true)->first();
            if ($previousYear) {
                $previousYear->update([
                    'is_current' => false,
                    'is_locked'  => true,
                    'status'     => 'LOCKED',
                    'locked_by'  => $userId,
                    'locked_at'  => now(),
                ]);
            }

            // تفعيل العام الجديد
            $newYear->update([
                'is_current'   => true,
                'is_locked'    => false,
                'status'       => 'ACTIVE',
                'activated_by' => $userId,
                'activated_at' => now(),
            ]);

            // تسجيل في سجل التدقيق
            try {
                DB::table('system_audit_trails')->insert([
                    'user_id'     => $userId,
                    'event_type'  => 'ACADEMIC_YEAR_ACTIVATED',
                    'description' => "تفعيل العام الدراسي: {$newYear->name} (ID: {$newYear->id})" .
                                     ($previousYear ? " | قفل العام السابق: {$previousYear->name}" : ''),
                    'ip_address'  => $request->ip(),
                    'payload'     => json_encode([
                        'new_year_id'      => $newYear->id,
                        'previous_year_id' => $previousYear?->id,
                    ]),
                    'created_at' => now(),
                ]);
            } catch (\Exception $e) {}

            return response()->json([
                'status'  => 'success',
                'message' => "✅ تم اعتماد وتفعيل العام الدراسي «{$newYear->name}» بنجاح على كامل المنظومة." .
                             ($previousYear ? " تم قفل العام السابق «{$previousYear->name}» نهائياً." : ''),
                'active_year'    => $newYear->fresh(),
                'locked_year'    => $previousYear,
            ]);
        });
    }

    /**
     * قفل عام دراسي نهائياً (بدون تفعيل عام جديد)
     */
    public function lockYear(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'year_id'       => 'required|exists:academic_years,id',
            'lock_reason'   => 'required|string|min:10|max:500',
        ], [
            'lock_reason.required' => 'يجب إدخال سبب القفل لتوثيق القرار في سجل التدقيق.',
            'lock_reason.min'      => 'يجب أن يكون سبب القفل وصفاً كافياً (10 أحرف على الأقل).',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $year = AcademicYear::findOrFail($request->year_id);
        $userId = Auth::id();

        if ($year->is_locked) {
            return response()->json([
                'status'  => 'warning',
                'message' => "العام «{$year->name}» مقفل بالفعل.",
            ]);
        }

        $year->update([
            'is_locked'  => true,
            'is_current' => false,
            'status'     => 'LOCKED',
            'locked_by'  => $userId,
            'locked_at'  => now(),
            'notes'      => ($year->notes ? $year->notes . "\n" : '') . "سبب القفل: " . $request->lock_reason,
        ]);

        try {
            DB::table('system_audit_trails')->insert([
                'user_id'     => $userId,
                'event_type'  => 'ACADEMIC_YEAR_LOCKED',
                'description' => "قفل العام الدراسي: {$year->name} | السبب: {$request->lock_reason}",
                'ip_address'  => $request->ip(),
                'payload'     => json_encode(['year_id' => $year->id, 'reason' => $request->lock_reason]),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {}

        return response()->json([
            'status'  => 'success',
            'message' => "🔒 تم قفل العام الدراسي «{$year->name}» نهائياً. لن يمكن إجراء أي تعديلات على بياناته.",
            'year'    => $year->fresh(),
        ]);
    }

    /**
     * نسخ مقررات من عام دراسي لآخر
     */
    public function duplicateCourses(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'source_year_id' => 'required|exists:academic_years,id',
            'target_year_id' => 'required|exists:academic_years,id|different:source_year_id',
            'overwrite'      => 'nullable|boolean',
        ], [
            'target_year_id.different' => 'العام المصدر والهدف يجب أن يكونا مختلفين.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $targetYear = AcademicYear::findOrFail($request->target_year_id);
        if ($targetYear->is_locked) {
            return response()->json([
                'status'  => 'error',
                'message' => "لا يمكن إضافة مقررات للعام «{$targetYear->name}» لأنه مقفل نهائياً.",
            ], 422);
        }

        return DB::transaction(function () use ($request, $targetYear): JsonResponse {
            $sourceCourses = Course::where('academic_year_id', $request->source_year_id)->get();

            if ($sourceCourses->isEmpty()) {
                return response()->json([
                    'status'  => 'warning',
                    'message' => 'العام الدراسي المصدر لا يحتوي على أي مقررات للنسخ.',
                ]);
            }

            // إذا طُلب الاستبدال: احذف المقررات الحالية في الهدف أولاً
            if ($request->boolean('overwrite', false)) {
                Course::where('academic_year_id', $targetYear->id)->delete();
            }

            $copied = 0;
            $skipped = 0;

            foreach ($sourceCourses as $course) {
                $exists = Course::where('academic_year_id', $targetYear->id)
                                ->where('study_year_id', $course->study_year_id)
                                ->where('department_id', $course->department_id)
                                ->where('semester', $course->semester)
                                ->where('code', $course->code)
                                ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                Course::create([
                    'academic_year_id'     => $targetYear->id,
                    'study_year_id'        => $course->study_year_id,
                    'department_id'        => $course->department_id,
                    'semester'             => $course->semester,
                    'code'                 => $course->code,
                    'name'                 => $course->name,
                    'credit_hours'         => $course->credit_hours,
                    'weekly_hours'         => $course->weekly_hours,
                    'assessment_system'    => $course->assessment_system,
                    'max_coursework_grade' => $course->max_coursework_grade,
                    'max_midterm_grade'    => $course->max_midterm_grade,
                    'max_final_grade'      => $course->max_final_grade,
                    'pass_grade'           => $course->pass_grade,
                    'max_score'            => $course->max_score,
                    'pass_min_score'       => $course->pass_min_score,
                    'second_round_max'     => $course->second_round_max,
                    'is_active'            => true,
                ]);
                $copied++;
            }

            return response()->json([
                'status'  => 'success',
                'message' => "تم نسخ {$copied} مقرراً بنجاح إلى العام «{$targetYear->name}»." . ($skipped > 0 ? " تخطي {$skipped} مقرراً مكرراً." : ''),
                'copied'  => $copied,
                'skipped' => $skipped,
            ]);
        });
    }

    /**
     * الحصول على حالة وإحصائيات عام دراسي محدد
     */
    public function getYearStatus(Request $request): JsonResponse
    {
        $yearId = $request->query('year_id');
        $year = $yearId
            ? AcademicYear::with(['activatedBy', 'lockedBy'])->find($yearId)
            : AcademicYear::with(['activatedBy', 'lockedBy'])->where('is_current', true)->first();

        if (!$year) {
            return response()->json(['status' => 'error', 'message' => 'لم يتم العثور على العام الدراسي.'], 404);
        }

        $stats = $year->getStats();
        $courses = Course::with(['studyYear', 'department'])
                         ->where('academic_year_id', $year->id)
                         ->where('is_active', true)
                         ->get()
                         ->groupBy('study_year_id');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'year'         => $year,
                'status_label' => $year->status_label,
                'is_active'    => $year->isActive(),
                'is_open'      => $year->isOpen(),
                'stats'        => $stats,
                'courses_by_level' => $courses,
            ],
        ]);
    }

    /**
     * تعديل بيانات العام الدراسي
     */
    public function updateYear(Request $request, $id): JsonResponse
    {
        $year = AcademicYear::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code'       => 'required|string|max:20|unique:academic_years,code,' . $year->id,
            'name'       => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
            'notes'      => 'nullable|string|max:500',
        ], [
            'code.required' => 'رمز العام الدراسي مطلوب.',
            'code.unique'   => 'رمز العام الدراسي مستخدم مسبقاً. يرجى اختيار رمز مختلف.',
            'name.required' => 'اسم العام الدراسي مطلوب.',
            'end_date.after'=> 'تاريخ انتهاء العام يجب أن يكون بعد تاريخ البداية.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        if ($year->is_locked) {
            return response()->json([
                'status'  => 'error',
                'message' => "لا يمكن تعديل العام الدراسي «{$year->name}» لأنه مقفل ومؤرشف نهائياً.",
            ], 422);
        }

        $userId = Auth::id();
        $oldData = $year->only(['code', 'name', 'start_date', 'end_date', 'notes']);

        $year->update([
            'code'       => $request->code,
            'name'       => $request->name,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'notes'      => $request->notes,
        ]);

        // توثيق في سجل التدقيق
        try {
            DB::table('system_audit_trails')->insert([
                'user_id'     => $userId,
                'event_type'  => 'ACADEMIC_YEAR_UPDATED',
                'description' => "تعديل بيانات العام الدراسي: {$year->name} (كود: {$year->code})",
                'ip_address'  => $request->ip(),
                'payload'     => json_encode([
                    'year_id'  => $year->id,
                    'old_data' => $oldData,
                    'new_data' => $year->only(['code', 'name', 'start_date', 'end_date', 'notes']),
                ]),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {}

        return response()->json([
            'status'  => 'success',
            'message' => "✅ تم تحديث بيانات العام الدراسي «{$year->name}» بنجاح.",
            'year'    => $year->fresh(),
        ]);
    }

    /**
     * حذف عام دراسي مع الفحص الأمني للقيود المرتبطة
     */
    /**
     * Inspect study year dependencies before deletion (Section 18 & 19).
     */
    public function inspectYear(Request $request, $id): JsonResponse
    {
        $year = AcademicYear::findOrFail($id);
        $service = app(\App\Services\StudyYearDeletionService::class);
        $inspection = $service->inspectDependencies($year);

        return response()->json([
            'status' => 'success',
            'data' => $inspection,
        ]);
    }

    /**
     * Safe study year deletion via StudyYearDeletionService (Section 18 & 19).
     */
    public function deleteYear(Request $request, $id): JsonResponse
    {
        $year = AcademicYear::findOrFail($id);
        $service = app(\App\Services\StudyYearDeletionService::class);
        $result = $service->safeDelete($year, Auth::id(), $request->ip());

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
                'data' => $result['inspection'] ?? null,
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
        ]);
    }

    // ===================================================================
    // الإعدادات الأخرى — Calendar, Schedules, Services
    // ===================================================================

    public function updateYearDates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year_id'      => 'required|exists:academic_years,id',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after:start_date',
            'weekend_days' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $year = AcademicYear::findOrFail($request->year_id);

        if ($year->is_locked) {
            return response()->json([
                'status'  => 'error',
                'message' => "لا يمكن تعديل تواريخ العام «{$year->name}» لأنه مقفل نهائياً.",
            ], 422);
        }

        $year->start_date = $request->start_date;
        $year->end_date = $request->end_date;
        $year->save();

        DB::table('system_central_settings')->updateOrInsert(
            ['setting_key' => 'weekend_days'],
            ['setting_value' => json_encode($request->weekend_days), 'updated_at' => now()]
        );

        return response()->json(['status' => 'success', 'message' => 'تم تحديث تواريخ العام الدراسي والعطلات بنجاح.']);
    }

    public function addCalendarEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year_id'    => 'required|exists:academic_years,id',
            'title'      => 'required|string|max:150',
            'event_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:event_date',
            'event_type' => 'required|string',
            'is_holiday' => 'nullable|boolean',
            'notes'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        DB::table('academic_calendar_events')->insert([
            'academic_year_id' => $request->year_id,
            'title'      => $request->title,
            'event_date' => $request->event_date,
            'end_date'   => $request->end_date,
            'event_type' => $request->event_type,
            'is_holiday' => $request->boolean('is_holiday', true) ? 1 : 0,
            'notes'      => $request->notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'تمت إضافة الفعالية / العطلة إلى التقويم بنجاح.']);
    }

    public function deleteCalendarEvent($id)
    {
        DB::table('academic_calendar_events')->where('id', $id)->delete();
        return response()->json(['status' => 'success', 'message' => 'تم حذف الفعالية من التقويم بنجاح.']);
    }

    public function addScheduleEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year_id'         => 'required|exists:academic_years,id',
            'title'           => 'required|string|max:150',
            'schedule_type'   => 'required|string|in:single,extended',
            'round_type'      => 'required|string|in:first,second',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'target_sections' => 'nullable|string',
            'target_levels'   => 'nullable|string',
            'notes'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $duration = 1;
        if ($request->schedule_type === 'extended' && $request->end_date) {
            $duration = (int)((strtotime($request->end_date) - strtotime($request->start_date)) / 86400) + 1;
        }

        DB::table('academic_schedules')->insert([
            'academic_year_id' => $request->year_id,
            'title'            => $request->title,
            'schedule_type'    => $request->schedule_type,
            'round_type'       => $request->round_type,
            'start_date'       => $request->start_date,
            'end_date'         => $request->schedule_type === 'extended' ? $request->end_date : null,
            'target_sections'  => $request->target_sections ?: 'كافة الشُعب التخصصية',
            'target_levels'    => $request->target_levels ?: 'كافة المراحل',
            'duration_days'    => $duration,
            'notes'            => $request->notes,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'تم إدراج موعد الفعالية والامتحان بنجاح.']);
    }

    public function deleteScheduleEvent($id)
    {
        DB::table('academic_schedules')->where('id', $id)->delete();
        return response()->json(['status' => 'success', 'message' => 'تم حذف الموعد المجدول بنجاح.']);
    }

    /**
     * @deprecated use activateYear() instead — kept for backward compatibility
     */
    public function setActiveYear(Request $request)
    {
        return $this->activateYear($request);
    }

    public function saveStudentServices(Request $request)
    {
        $services = $request->input('services', []);
        DB::table('system_central_settings')->updateOrInsert(
            ['setting_key' => 'student_services_config'],
            ['setting_value' => json_encode($services, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]
        );
        return response()->json(['status' => 'success', 'message' => 'تم حفظ فترات ومواعيد الخدمات الطلابية بنجاح.']);
    }

    public function saveAdminPeriods(Request $request)
    {
        $periods = $request->input('periods', []);
        DB::table('system_central_settings')->updateOrInsert(
            ['setting_key' => 'admin_periods_config'],
            ['setting_value' => json_encode($periods, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]
        );
        return response()->json(['status' => 'success', 'message' => 'تم حفظ وضبط الفترات الإدارية ونوافذ الطعون بنجاح.']);
    }

    public function updateResultsGateways(Request $request)
    {
        DB::table('system_central_settings')->updateOrInsert(['setting_key' => 'gate_transport_first_round'], ['setting_value' => $request->transport_first_round ?? 'closed', 'updated_at' => now()]);
        DB::table('system_central_settings')->updateOrInsert(['setting_key' => 'gate_second_round_all'], ['setting_value' => $request->second_round_all ?? 'closed', 'updated_at' => now()]);
        DB::table('system_central_settings')->updateOrInsert(['setting_key' => 'gate_diploma_first_round_manual'], ['setting_value' => $request->diploma_manual ?? 'closed', 'updated_at' => now()]);
        DB::table('system_central_settings')->updateOrInsert(['setting_key' => 'gate_diploma_first_round_auto'], ['setting_value' => $request->diploma_auto ? '1' : '0', 'updated_at' => now()]);
        DB::table('system_central_settings')->updateOrInsert(['setting_key' => 'gate_diploma_open_at'], ['setting_value' => $request->diploma_open_at ?? '', 'updated_at' => now()]);
        DB::table('system_central_settings')->updateOrInsert(['setting_key' => 'gate_diploma_close_at'], ['setting_value' => $request->diploma_close_at ?? '', 'updated_at' => now()]);

        return response()->json(['status' => 'success', 'message' => 'تم تحديث حالات بوابات إعلان النتائج بنجاح.']);
    }

    public function addPosition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_code' => ['required', 'regex:/^\d{4}$/', 'unique:organizational_positions,admin_code'],
            'title'      => 'required|string|max:150',
            'department' => 'required|string|max:150',
            'user_id'    => 'nullable|exists:users,id',
        ], [
            'admin_code.regex'  => 'رمز الصفة الإداري يجب أن يتكون من 4 أرقام عددية تماماً.',
            'admin_code.unique' => 'الرمز الإداري مستخدم مسبقاً لصفة أخرى.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        DB::table('organizational_positions')->insert([
            'admin_code' => $request->admin_code,
            'title'      => $request->title,
            'department' => $request->department,
            'user_id'    => $request->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'تم تعريف المسمى الوظيفي الإداري بنجاح.']);
    }

    public function updatePosition(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'admin_code' => ['required', 'regex:/^\d{4}$/', 'unique:organizational_positions,admin_code,' . $id],
            'title'      => 'required|string|max:150',
            'department' => 'required|string|max:150',
            'user_id'    => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        DB::table('organizational_positions')->where('id', $id)->update([
            'admin_code' => $request->admin_code,
            'title'      => $request->title,
            'department' => $request->department,
            'user_id'    => $request->user_id,
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'تم تحديث بيانات الصفة ونقل التكليف بنجاح.']);
    }

    public function deletePosition($id)
    {
        DB::table('organizational_positions')->where('id', $id)->delete();
        return response()->json(['status' => 'success', 'message' => 'تم حذف المسمى الوظيفي بنجاح.']);
    }

    // ===================================================================
    // OPERATIONAL WINDOWS ENGINE — محرك النوافذ التشغيلية المؤتمتة
    // ===================================================================

    /**
     * استرجاع قائمة النوافذ التشغيلية مع الاستثناءات وحالة الإغلاق المباشرة
     */
    public function getOperationalWindows(Request $request): JsonResponse
    {
        $yearId = $request->query('year_id');
        $activeYear = $yearId ? AcademicYear::find($yearId) : AcademicYear::where('is_current', true)->first();
        $yearId = $activeYear ? $activeYear->id : 1;

        $windows = OperationalWindow::with(['creator:id,name,email', 'exceptions.branch:id,name,code', 'exceptions.granter:id,name'])
            ->where('academic_year_id', $yearId)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($w) {
                $now = Carbon::now();
                $isOpen = $w->is_active && $now->between($w->start_at, $w->end_at);
                $remainingSeconds = max(0, $now->diffInSeconds($w->end_at, false));

                return [
                    'id'                => $w->id,
                    'academic_year_id'  => $w->academic_year_id,
                    'window_type'       => $w->window_type,
                    'title'             => $w->title,
                    'start_at'          => $w->start_at ? $w->start_at->toIso8601String() : null,
                    'end_at'            => $w->end_at ? $w->end_at->toIso8601String() : null,
                    'is_active'         => (bool)$w->is_active,
                    'is_open_hq'        => $isOpen,
                    'remaining_seconds' => $remainingSeconds,
                    'created_by_name'   => $w->creator?->name ?? 'الإدارة المركزية',
                    'exceptions'        => $w->exceptions->map(function ($ex) use ($now) {
                        return [
                            'id'              => $ex->id,
                            'branch_id'       => $ex->branch_id,
                            'branch_name'     => $ex->branch?->name ?? 'فرع غير معروف',
                            'branch_code'     => $ex->branch?->code,
                            'extended_until'  => $ex->extended_until ? $ex->extended_until->toIso8601String() : null,
                            'is_valid'        => $ex->extended_until && $ex->extended_until->isAfter($now),
                            'reason'          => $ex->reason,
                            'granted_by_name' => $ex->granter?->name ?? 'المدير العام',
                        ];
                    }),
                ];
            });

        $branches = Branch::select('id', 'name', 'code', 'city')->where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'windows'  => $windows,
                'branches' => $branches,
                'year'     => $activeYear,
            ]
        ]);
    }

    /**
     * حفظ / تحديث نافذة تشغيلية
     */
    public function saveOperationalWindow(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id'               => 'nullable|exists:operational_windows,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'window_type'      => 'required|string|max:50',
            'title'            => 'required|string|max:200',
            'start_at'         => 'required|date',
            'end_at'           => 'required|date|after:start_at',
            'is_active'        => 'nullable|boolean',
        ], [
            'end_at.after'     => 'تاريخ ونهاية النافذة يجب أن يكون لاحقاً لتاريخ البداية.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $userId = Auth::id() ?? 1;

        $window = OperationalWindow::updateOrCreate(
            ['id' => $request->id],
            [
                'academic_year_id' => $request->academic_year_id,
                'window_type'      => $request->window_type,
                'title'            => $request->title,
                'start_at'         => $request->start_at,
                'end_at'           => $request->end_at,
                'is_active'        => $request->boolean('is_active', true),
                'created_by'       => $userId,
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم ضبط وتحديث النافذة التشغيلية بنجاح.',
            'window'  => $window,
        ]);
    }

    /**
     * تبديل حالة النافذة التشغيلية (تفعيل / إيقاف لحظي)
     */
    public function toggleOperationalWindow(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'window_id' => 'required|exists:operational_windows,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $window = OperationalWindow::findOrFail($request->window_id);
        $window->is_active = !$window->is_active;
        $window->save();

        $stateMsg = $window->is_active ? 'تفعيل النافذة التشغيلية' : 'إيقاف وتعطيل النافذة التشغيلية';

        try {
            DB::table('system_audit_trails')->insert([
                'user_id'     => Auth::id() ?? 1,
                'event_type'  => 'OPERATIONAL_WINDOW_TOGGLE',
                'description' => "{$stateMsg}: {$window->title} ({$window->window_type})",
                'ip_address'  => $request->ip(),
                'payload'     => json_encode(['window_id' => $window->id, 'is_active' => $window->is_active]),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {}

        return response()->json([
            'status'    => 'success',
            'message'   => "تم {$stateMsg} بنجاح.",
            'is_active' => $window->is_active,
        ]);
    }

    /**
     * منح استثناء وتمديد زمني لفرع محدد
     */
    public function addWindowException(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'window_id'       => 'required|exists:operational_windows,id',
            'branch_id'       => 'required|exists:branches,id',
            'extended_until'  => 'required|date|after:now',
            'reason'          => 'required|string|min:5|max:500',
        ], [
            'extended_until.after' => 'تاريخ التمديد الزمني يجب أن يكون في المستقبل.',
            'reason.required'      => 'يجب توضيح سبب منح الاستثناء للفرع.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $userId = Auth::id() ?? 1;

        $exception = OperationalWindowException::updateOrCreate(
            [
                'window_id' => $request->window_id,
                'branch_id' => $request->branch_id,
            ],
            [
                'granted_by'     => $userId,
                'extended_until' => $request->extended_until,
                'reason'         => $request->reason,
            ]
        );

        $branch = Branch::find($request->branch_id);
        $window = OperationalWindow::find($request->window_id);

        try {
            DB::table('system_audit_trails')->insert([
                'user_id'     => $userId,
                'event_type'  => 'BRANCH_WINDOW_EXCEPTION_GRANTED',
                'description' => "منح استثناء وتمديد زمني لـ ({$branch?->name}) على نافذة ({$window?->title}) حتى {$request->extended_until} | السبب: {$request->reason}",
                'ip_address'  => $request->ip(),
                'payload'     => json_encode($exception->toArray()),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {}

        return response()->json([
            'status'    => 'success',
            'message'   => "تم منح الاستثناء والتمديد الزمني لـ «{$branch?->name}» بنجاح.",
            'exception' => $exception->load(['branch:id,name,code', 'granter:id,name']),
        ]);
    }

    /**
     * إلغاء استثناء الفرع
     */
    public function deleteWindowException($id): JsonResponse
    {
        $exception = OperationalWindowException::with(['branch', 'window'])->findOrFail($id);
        $branchName = $exception->branch?->name;
        $windowTitle = $exception->window?->title;

        $exception->delete();

        try {
            DB::table('system_audit_trails')->insert([
                'user_id'     => Auth::id() ?? 1,
                'event_type'  => 'BRANCH_WINDOW_EXCEPTION_REVOKED',
                'description' => "إلغاء الاستثناء الزمني الممنوح لـ ({$branchName}) على نافذة ({$windowTitle})",
                'ip_address'  => request()->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {}

        return response()->json([
            'status'  => 'success',
            'message' => "تم إلغاء الاستثناء الزمني للفرع «{$branchName}» بنجاح.",
        ]);
    }

    // ===================================================================
    // STUDENT PROGRESSION & ROLLOVER ENGINE — محرك الترحيل الأكاديمي
    // ===================================================================

    /**
     * محاكاة تجريبية للترحيل الأكاديمي السنوي (Dry Run)
     */
    public function simulateStudentProgression(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id'   => 'required|exists:academic_years,id|different:from_academic_year_id',
            'max_second_round'      => 'nullable|integer|min:0|max:5',
        ], [
            'to_academic_year_id.different' => 'العام الدراسي الجديد يجب أن يكون مختلفاً عن العام الحالي.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $fromYear = AcademicYear::findOrFail($request->from_academic_year_id);
        $toYear = AcademicYear::findOrFail($request->to_academic_year_id);
        $maxSecondRound = (int)$request->input('max_second_round', 2);

        // جلب الطلاب المقيدين
        $students = Student::with(['branch:id,name,code', 'currentStudyYear:id,name,level_order', 'department:id,name'])
            ->whereIn('academic_status', ['ENROLLED_ACTIVE', 'PENDING_HQ', 'APPROVED'])
            ->get();

        $evaluated = 0;
        $promotedList = [];
        $graduatedList = [];
        $secondRoundList = [];
        $heldBackList = [];

        // خريطة المراحل الدراسية التالية
        $studyYears = StudyYear::orderBy('level_order')->get()->keyBy('level_order');
        $maxLevel = $studyYears->max('level_order') ?: 3;

        foreach ($students as $student) {
            $evaluated++;
            $currentLevel = $student->currentStudyYear?->level_order ?: 1;

            // فحص نتائج المقررات
            $grades = StudentGrade::where('student_id', $student->id)->get();
            $totalCourses = $grades->count();
            $failedCourses = 0;

            foreach ($grades as $g) {
                $finalScore = $g->both_semesters_grand_total ?? $g->final_grand_total ?? $g->total_grade ?? 0;
                $passed = ($g->passed_total_rule !== null) ? (bool)$g->passed_total_rule : ($finalScore >= 50);
                if (!$passed) {
                    $failedCourses++;
                }
            }

            // تحديد القرار الأكاديمي
            if ($failedCourses === 0) {
                if ($currentLevel >= $maxLevel) {
                    // السنة النهائية -> متخرج
                    $graduatedList[] = [
                        'student_id'   => $student->id,
                        'name'         => $student->full_name,
                        'seat_number'  => $student->academic_number,
                        'branch'       => $student->branch?->name,
                        'department'   => $student->department?->name,
                        'current_level'=> $student->currentStudyYear?->name,
                        'next_action'  => 'تخرج ومنح الشهادة',
                        'failed_count' => 0,
                    ];
                } else {
                    // ترحيل للمرحلة التالية
                    $nextYear = $studyYears->get($currentLevel + 1);
                    $promotedList[] = [
                        'student_id'   => $student->id,
                        'name'         => $student->full_name,
                        'seat_number'  => $student->academic_number,
                        'branch'       => $student->branch?->name,
                        'department'   => $student->department?->name,
                        'current_level'=> $student->currentStudyYear?->name,
                        'next_level'   => $nextYear?->name ?? 'السنة التالية',
                        'next_study_year_id' => $nextYear?->id,
                        'next_action'  => 'نجاح وترفيع للسنة الأعلى',
                        'failed_count' => 0,
                    ];
                }
            } elseif ($failedCourses <= $maxSecondRound) {
                // دور ثانٍ
                $secondRoundList[] = [
                    'student_id'   => $student->id,
                    'name'         => $student->full_name,
                    'seat_number'  => $student->academic_number,
                    'branch'       => $student->branch?->name,
                    'department'   => $student->department?->name,
                    'current_level'=> $student->currentStudyYear?->name,
                    'next_action'  => "دور ثانٍ ({$failedCourses} مقررات)",
                    'failed_count' => $failedCourses,
                ];
            } else {
                // إعادة السنة
                $heldBackList[] = [
                    'student_id'   => $student->id,
                    'name'         => $student->full_name,
                    'seat_number'  => $student->academic_number,
                    'branch'       => $student->branch?->name,
                    'department'   => $student->department?->name,
                    'current_level'=> $student->currentStudyYear?->name,
                    'next_action'  => "رسوب وإعادة قيد ({$failedCourses} مقررات)",
                    'failed_count' => $failedCourses,
                ];
            }
        }

        // فحص عدد المقررات في العامين
        $sourceCoursesCount = Course::where('academic_year_id', $fromYear->id)->count();
        $targetCoursesCount = Course::where('academic_year_id', $toYear->id)->count();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'from_year'     => $fromYear->name,
                'to_year'       => $toYear->name,
                'summary'       => [
                    'total_evaluated'     => $evaluated,
                    'promoted_count'      => count($promotedList),
                    'graduated_count'     => count($graduatedList),
                    'second_round_count'  => count($secondRoundList),
                    'held_back_count'     => count($heldBackList),
                    'source_courses_count'=> $sourceCoursesCount,
                    'target_courses_count'=> $targetCoursesCount,
                ],
                'preview'       => [
                    'promoted'     => array_slice($promotedList, 0, 15),
                    'graduated'    => array_slice($graduatedList, 0, 15),
                    'second_round' => array_slice($secondRoundList, 0, 15),
                    'held_back'    => array_slice($heldBackList, 0, 15),
                ]
            ]
        ]);
    }

    /**
     * تنفيذ الترحيل الأكاديمي الفعلي للعام الجديد
     */
    public function executeStudentProgression(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id'   => 'required|exists:academic_years,id|different:from_academic_year_id',
            'max_second_round'      => 'nullable|integer|min:0|max:5',
            'copy_courses'          => 'nullable|boolean',
            'confirm_rollover'      => 'required|accepted',
        ], [
            'confirm_rollover.accepted' => 'يجب تأكيد الموافقة على تنفيذ الترحيل السنوي.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        // أمان حرج: حصر الترحيل الأكاديمي في الإدارة العامة
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && !$user->isSuperAdmin()) {
            return response()->json([
                'status'  => 'error',
                'message' => '❌ إجراء محظور: تنفيذ الترحيل الأكاديمي السنوي محصور حصراً في إدارة الامتحانات بالرئاسة المركزية.',
            ], 403);
        }

        return DB::transaction(function () use ($request, $user): JsonResponse {
            $fromYear = AcademicYear::findOrFail($request->from_academic_year_id);
            $toYear = AcademicYear::findOrFail($request->to_academic_year_id);
            $userId = Auth::id() ?? 1;
            $maxSecondRound = (int)$request->input('max_second_round', 2);

            $studyYears = StudyYear::orderBy('level_order')->get()->keyBy('level_order');
            $maxLevel = $studyYears->max('level_order') ?: 3;

            $studentsQuery = Student::whereIn('academic_status', ['ENROLLED_ACTIVE', 'PENDING_HQ', 'APPROVED']);
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $studentsQuery->where('branch_id', $request->branch_id);
            }
            $students = $studentsQuery->get();

            $evaluated = 0;
            $promoted = 0;
            $graduated = 0;
            $secondRound = 0;
            $heldBack = 0;

            foreach ($students as $student) {
                $evaluated++;
                $currentLevel = $student->currentStudyYear?->level_order ?: 1;

                $grades = StudentGrade::where('student_id', $student->id)->get();
                $failedCourses = 0;

                foreach ($grades as $g) {
                    $finalScore = $g->both_semesters_grand_total ?? $g->final_grand_total ?? $g->total_grade ?? 0;
                    $passed = ($g->passed_total_rule !== null) ? (bool)$g->passed_total_rule : ($finalScore >= 50);
                    if (!$passed) {
                        $failedCourses++;
                    }
                }

                $rolloverAction = 'HELD_BACK';

                if ($failedCourses === 0) {
                    if ($currentLevel >= $maxLevel) {
                        // متخرج
                        $student->update([
                            'academic_status' => 'GRADUATED',
                        ]);
                        $graduated++;
                        $rolloverAction = 'GRADUATED';
                    } else {
                        // ترفيع
                        $nextYear = $studyYears->get($currentLevel + 1);
                        if ($nextYear) {
                            $student->update([
                                'current_study_year_id'     => $nextYear->id,
                                'enrolled_academic_year_id' => $toYear->id,
                                'academic_status'           => 'ENROLLED_ACTIVE',
                            ]);
                            $promoted++;
                            $rolloverAction = 'PROMOTED';
                        }
                    }
                } elseif ($failedCourses <= $maxSecondRound) {
                    // دور ثانٍ
                    $student->update([
                        'enrolled_academic_year_id' => $toYear->id,
                        'academic_status'           => 'ENROLLED_ACTIVE',
                    ]);
                    $secondRound++;
                    $rolloverAction = 'SECOND_ROUND';
                } else {
                    // إعادة قيد بنفس السنة
                    $student->update([
                        'enrolled_academic_year_id' => $toYear->id,
                        'academic_status'           => 'ENROLLED_ACTIVE',
                    ]);
                    $heldBack++;
                    $rolloverAction = 'HELD_BACK';
                }

                // تسجيل الأثر في السجل الموحد لتاريخ قيد الطالب
                try {
                    \App\Models\StudentStatusHistory::create([
                        'student_id'    => $student->id,
                        'event_type'    => 'ROLLOVER_' . $rolloverAction,
                        'old_status'    => 'ENROLLED_ACTIVE',
                        'new_status'    => ($rolloverAction === 'GRADUATED') ? 'GRADUATED' : 'ENROLLED_ACTIVE',
                        'reason'        => "ترحيل أكاديمي سنوي من ({$fromYear->name}) إلى ({$toYear->name})",
                        'meta'          => [
                            'from_year_id'   => $fromYear->id,
                            'to_year_id'     => $toYear->id,
                            'action'         => $rolloverAction,
                            'failed_courses' => $failedCourses,
                        ],
                        'changed_by'    => $userId,
                        'event_date'    => now(),
                    ]);
                } catch (\Exception $e) {}
            }

            // نسخ المقررات إذا طُلب ذلك
            $copiedCourses = 0;
            if ($request->boolean('copy_courses', true)) {
                $sourceCourses = Course::where('academic_year_id', $fromYear->id)->get();
                foreach ($sourceCourses as $course) {
                    $exists = Course::where('academic_year_id', $toYear->id)
                                    ->where('study_year_id', $course->study_year_id)
                                    ->where('department_id', $course->department_id)
                                    ->where('semester', $course->semester)
                                    ->where('code', $course->code)
                                    ->exists();
                    if (!$exists) {
                        Course::create([
                            'academic_year_id'     => $toYear->id,
                            'study_year_id'        => $course->study_year_id,
                            'department_id'        => $course->department_id,
                            'semester'             => $course->semester,
                            'code'                 => $course->code,
                            'name'                 => $course->name,
                            'credit_hours'         => $course->credit_hours,
                            'weekly_hours'         => $course->weekly_hours,
                            'assessment_system'    => $course->assessment_system,
                            'max_coursework_grade' => $course->max_coursework_grade,
                            'max_midterm_grade'    => $course->max_midterm_grade,
                            'max_final_grade'      => $course->max_final_grade,
                            'pass_grade'           => $course->pass_grade,
                            'max_score'            => $course->max_score,
                            'pass_min_score'       => $course->pass_min_score,
                            'second_round_max'     => $course->second_round_max,
                            'is_active'            => true,
                        ]);
                        $copiedCourses++;
                    }
                }
            }

            // تسجيل سجل الترحيل الأكاديمي
            $log = AcademicYearRolloverLog::create([
                'from_academic_year_id' => $fromYear->id,
                'to_academic_year_id'   => $toYear->id,
                'executed_by'           => $userId,
                'students_evaluated'    => $evaluated,
                'students_promoted'     => $promoted,
                'students_held_back'    => $heldBack,
                'students_graduated'    => $graduated,
                'students_second_round' => $secondRound,
                'courses_copied'        => $copiedCourses,
                'summary_json'          => [
                    'from_year_name' => $fromYear->name,
                    'to_year_name'   => $toYear->name,
                    'timestamp'      => now()->toIso8601String(),
                ],
                'status'                => 'COMPLETED',
                'notes'                 => "تم ترحيل الطلاب من {$fromYear->name} إلى {$toYear->name} بنجاح.",
            ]);

            // سجل التدقيق الأمني
            try {
                DB::table('system_audit_trails')->insert([
                    'user_id'     => $userId,
                    'event_type'  => 'ACADEMIC_YEAR_ROLLOVER_EXECUTED',
                    'description' => "تنفيذ الترحيل الأكاديمي السنوي من ({$fromYear->name}) إلى ({$toYear->name}) | ترفيع: {$promoted}، تخرج: {$graduated}، دور ثان: {$secondRound}، إعادة: {$heldBack}",
                    'ip_address'  => $request->ip(),
                    'payload'     => json_encode($log->toArray()),
                    'created_at'  => now(),
                ]);
            } catch (\Exception $e) {}

            return response()->json([
                'status'  => 'success',
                'message' => "🎉 تم تنفيذ الترحيل الأكاديمي السنوي بنجاح: تم ترفيع {$promoted} طالباً، تخرج {$graduated}، تحويل {$secondRound} للدور الثاني، وإعادة قيد {$heldBack} طالباً." . ($copiedCourses > 0 ? " مع نسخ {$copiedCourses} مقرراً." : ''),
                'log'     => $log,
            ]);
        });
    }

    /**
     * استرجاع سجلات الترحيل السابقة
     */
    public function getRolloverLogs(Request $request): JsonResponse
    {
        $logs = AcademicYearRolloverLog::with(['fromYear:id,name,code', 'toYear:id,name,code', 'executor:id,name'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $logs,
        ]);
    }
}
