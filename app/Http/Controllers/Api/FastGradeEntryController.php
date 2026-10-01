<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\GradeBatch;
use App\Models\StudentGrade;
use App\Models\Student;
use App\Models\Course;
use App\Services\ForensicGradeLoggerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FastGradeEntryController extends Controller
{
    protected ForensicGradeLoggerService $gradeLogger;

    public function __construct(ForensicGradeLoggerService $gradeLogger)
    {
        $this->gradeLogger = $gradeLogger;
    }

    /**
     * Get or create a grade batch and return all enrolled students with their grade rows.
     */
    public function getGradeSheet(Request $request): JsonResponse
    {
        $branchId = (int) $request->input('branch_id', 1);
        $academicYearId = (int) $request->input('academic_year_id', 1);
        $courseId = $request->has('course_id') ? (int) $request->input('course_id') : null;
        $studyYearId = $request->has('study_year_id') ? (int) $request->input('study_year_id') : null;
        $departmentId = (int) $request->input('department_id', 1);
        $semester = (int) $request->input('semester', 1);

        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope()) {
            $branchId = $user->branch_id ?? 1;
        }

        // Determine course
        if ($courseId) {
            $course = Course::find($courseId);
            if ($course) {
                $studyYearId = $course->study_year_id;
                $departmentId = $course->department_id;
            }
        } elseif ($studyYearId) {
            $course = Course::where('study_year_id', $studyYearId)->first();
        } else {
            $course = Course::first();
            if ($course) {
                $studyYearId = $course->study_year_id;
                $departmentId = $course->department_id;
            }
        }

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على مقررات دراسية للشعبة المحددة.',
            ], 404);
        }

        $studyYearId = $course->study_year_id;
        $departmentId = $course->department_id;
        $courseId = $course->id;

        $batchCode = sprintf(
            "BATCH-%s-%s-%s-SEM%s",
            $branchId,
            $academicYearId,
            $courseId,
            $semester
        );

        $batch = GradeBatch::firstOrCreate(
            [
                'batch_code' => $batchCode,
            ],
            [
                'branch_id' => $branchId,
                'academic_year_id' => $academicYearId,
                'study_year_id' => $studyYearId,
                'department_id' => $departmentId,
                'semester' => $semester,
                'status' => 'DRAFT',
            ]
        );

        // Fetch all active enrolled students in this branch, department, and study year
        $students = Student::where('branch_id', $branchId)
            ->where('department_id', $departmentId)
            ->where('current_study_year_id', $studyYearId)
            ->where('academic_status', 'ENROLLED_ACTIVE')
            ->orderBy('family_name')
            ->get();

        // Fallback: if no active students for branch in this year, query without branch scope for demo preview
        if ($students->isEmpty()) {
            $students = Student::where('department_id', $departmentId)
                ->where('current_study_year_id', $studyYearId)
                ->orderBy('family_name')
                ->get();
        }

        // Ensure student grade row exists for each student in this batch
        foreach ($students as $student) {
            $grade = StudentGrade::firstOrCreate([
                'grade_batch_id' => $batch->id,
                'student_id' => $student->id,
                'course_id' => $course->id,
            ]);

            // If empty, auto-calculate
            if ($grade->total_grade === null && ($grade->coursework_grade !== null || $grade->daily_activities !== null)) {
                $grade->calculateResult();
                $grade->save();
            }
        }

        $grades = StudentGrade::with('student')
            ->where('grade_batch_id', $batch->id)
            ->where('course_id', $course->id)
            ->get();

        // List of courses for this study year
        $availableCourses = Course::where('study_year_id', $studyYearId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        // Format sheet for frontend binding
        $sheet = $grades->map(function ($g) use ($course) {
            return [
                'id' => $g->id,
                'student_id' => $g->student_id,
                'academic_number' => $g->student ? $g->student->academic_number : '',
                'student_name' => $g->student ? $g->student->full_name : '',
                // Semester system columns
                'daily_activities' => (float) ($g->daily_activities ?? 0),
                'applications_avg' => (float) ($g->applications_avg ?? 0),
                'midterm' => (float) ($g->midterm_grade ?? 0),
                'coursework' => (float) ($g->semester_work_total ?? $g->coursework_grade ?? 0),
                'final_exam' => (float) ($g->semester_final_exam ?? $g->final_exam_grade ?? 0),
                'second_semester_final_exam' => (float) ($g->second_semester_final_exam ?? 0),
                // Annual Periods system columns (Year 3)
                'period1_activities' => (float) ($g->period1_activities ?? 0),
                'period1_written' => (float) ($g->period1_written ?? 0),
                'period1_exam' => (float) ($g->period1_exam ?? 0),
                'period1_total' => (float) ($g->period1_total ?? 0),
                'period2_activities' => (float) ($g->period2_activities ?? 0),
                'period2_written' => (float) ($g->period2_written ?? 0),
                'period2_exam' => (float) ($g->period2_exam ?? 0),
                'period2_total' => (float) ($g->period2_total ?? 0),
                'periods_combined_total' => (float) ($g->periods_combined_total ?? 0),
                'year_end_exam' => (float) ($g->year_end_exam ?? $g->final_exam_grade ?? 0),
                // Results & Rules
                'total' => (float) ($g->total_grade ?? $g->final_grand_total ?? 0),
                'letter_grade' => $g->letter_grade ?? 'F',
                'status' => $g->status ?? 'RESIT',
                'passed_exam_rule' => (bool) $g->passed_exam_rule,
                'passed_total_rule' => (bool) $g->passed_total_rule,
                'academic_status_note' => $g->academic_status_note ?? '',
            ];
        });

        return response()->json([
            'success' => true,
            'batch' => $batch,
            'course' => $course,
            'study_year_id' => $studyYearId,
            'assessment_system' => $course->assessment_system,
            'available_courses' => $availableCourses,
            'is_editable' => ($batch->status === 'DRAFT' || $batch->status === 'HQ_REJECTED'),
            'grades' => $grades,
            'sheet' => $sheet,
        ]);
    }

    /**
     * Fast-save grades with instant validation, calculation, and forensic auditing.
     */
    public function saveBatchGrades(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'required|exists:grade_batches,id',
            'grades' => 'required|array',
            'grades.*.id' => 'nullable|exists:student_grades,id',
            'grades.*.student_id' => 'nullable|exists:students,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $ipAddress = $request->ip() ?? '127.0.0.1';
        $userAgent = $request->userAgent();
        $reason = $request->reason ?? 'رصد درجات وفق اللائحة المعتمدة لوزارة الأوقاف';

        $updatedCount = 0;

        DB::transaction(function () use ($request, $ipAddress, $userAgent, $reason, &$updatedCount) {
            $batch = GradeBatch::lockForUpdate()->findOrFail($request->batch_id);

            // 1. عزل الفروع: التحقق من أن المستخدم يتبع نفس فرع الدفعة ما لم يكن يملك صلاحية مركزية
            $user = Auth::user();
            if ($user && !$user->hasGlobalAccessScope() && !empty($user->branch_id)) {
                if ((int)$batch->branch_id !== (int)$user->branch_id) {
                    throw new \Exception('❌ غير مصرح: لا يمكنك رصد أو تعديل درجات لفرع آخر.');
                }
            }

            if ($batch->isLocked()) {
                throw new \Exception('لا يمكن تعديل هذه الدفعة؛ الدفعة مقفلة أو مرفوعة للاعتماد بالفعل.');
            }

            // 2. التحقق من النوافذ التشغيلية ديناميكياً بناءً على الدفعة ونوع التقييم
            if ($user && !$user->isSuperAdmin()) {
                $windowType = match ((int)$batch->semester) {
                    2 => 'S2_COURSEWORK',
                    default => 'S1_COURSEWORK',
                };

                // التحقق مما إذا كانت الدفعة تتضمن رصد امتحانات نهائية
                $savingFinalExam = false;
                foreach ($request->grades as $g) {
                    if (!empty($g['final_exam']) || !empty($g['final_exam_grade']) || !empty($g['semester_final_exam']) || !empty($g['year_end_exam'])) {
                        $savingFinalExam = true;
                        break;
                    }
                }
                if ($savingFinalExam) {
                    $windowType = ((int)$batch->semester === 2) ? 'S2_FINAL' : 'S1_FINAL';
                }

                $activeYear = \App\Models\AcademicYear::where('is_current', true)->first();
                if ($activeYear) {
                    $window = \App\Models\OperationalWindow::where('academic_year_id', $activeYear->id)
                        ->where('window_type', $windowType)
                        ->where('is_active', true)
                        ->first();

                    if ($window && !$window->isOpenForBranch($batch->branch_id)) {
                        throw new \Exception("❌ عفواً، انتهت الفترة المحددة نظاماً لرصد الدرجات ({$window->title}) وتم إغلاق النظام آلياً لهذا الفرع.");
                    }
                }
            }

            $fieldsToTrack = [
                'coursework_grade',
                'midterm_grade',
                'final_exam_grade',
                'daily_activities',
                'applications_avg',
                'semester_final_exam',
                'second_semester_final_exam',
                'period1_activities',
                'period1_written',
                'period1_exam',
                'period2_activities',
                'period2_written',
                'period2_exam',
                'year_end_exam',
                'final_exam_grade_qr_intisab',
                'second_round_grade',
            ];

            foreach ($request->grades as $item) {
                if (!empty($item['id'])) {
                    $grade = StudentGrade::lockForUpdate()->findOrFail($item['id']);
                } elseif (!empty($item['student_id'])) {
                    $grade = StudentGrade::lockForUpdate()
                        ->where('grade_batch_id', $batch->id)
                        ->where('student_id', $item['student_id'])
                        ->firstOrFail();
                } else {
                    continue;
                }

                $course = $grade->course;

                // Map aliases if sent from shorthand frontend
                if (isset($item['coursework']) && !isset($item['coursework_grade'])) {
                    $item['coursework_grade'] = $item['coursework'];
                }
                if (isset($item['midterm']) && !isset($item['midterm_grade'])) {
                    $item['midterm_grade'] = $item['midterm'];
                }
                if (isset($item['final_exam']) && !isset($item['final_exam_grade'])) {
                    $item['final_exam_grade'] = $item['final_exam'];
                }

                // Field limits validation
                if ($course) {
                    if (isset($item['coursework_grade']) && $item['coursework_grade'] > $course->max_coursework_grade) {
                        throw new \Exception("درجة أعمال السنة للطالب ({$grade->student->full_name}) تتجاوز الحد الأقصى المقرر ({$course->max_coursework_grade}).");
                    }
                    if (isset($item['midterm_grade']) && $item['midterm_grade'] > $course->max_midterm_grade) {
                        throw new \Exception("درجة الامتحان النصفي للطالب ({$grade->student->full_name}) تتجاوز الحد الأقصى المقرر ({$course->max_midterm_grade}).");
                    }
                    if (isset($item['final_exam_grade']) && $item['final_exam_grade'] > $course->max_final_grade) {
                        throw new \Exception("درجة الامتحان النهائي للطالب ({$grade->student->full_name}) تتجاوز الحد الأقصى المقرر ({$course->max_final_grade}).");
                    }
                }

                foreach ($fieldsToTrack as $f) {
                    if (array_key_exists($f, $item) && $item[$f] !== null) {
                        $newVal = round((float) $item[$f], 2);
                        $oldVal = $grade->$f !== null ? round((float) $grade->$f, 2) : null;

                        if ($oldVal !== $newVal) {
                            $this->gradeLogger->logChange($grade, $f, $oldVal, $newVal, $ipAddress, $userAgent, $reason);
                            $grade->$f = $newVal;
                        }
                    }
                }

                $grade->calculateResult();
                $grade->save();
                $updatedCount++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "تم حفظ وتدقيق درجات {$updatedCount} طالب بنجاح وفق لائحة وزارة الأوقاف وتسجيل الأثر الجنائي.",
        ]);
    }

    /**
     * Branch submits batch for HQ Approval.
     */
    public function submitBatchToHq(GradeBatch $batch): JsonResponse
    {
        if ($batch->status !== 'DRAFT' && $batch->status !== 'HQ_REJECTED') {
            return response()->json([
                'success' => false,
                'message' => 'الدفعة مرسلة أو معتمدة بالفعل.',
            ], 422);
        }

        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope() && !empty($user->branch_id)) {
            if ((int)$batch->branch_id !== (int)$user->branch_id) {
                return response()->json([
                    'success' => false,
                    'message' => '❌ غير مصرح: لا يمكنك رفع كشوف درجات لفرع آخر للإدارة العامة.',
                ], 403);
            }
        }
        $batch->update([
            'status' => 'SUBMITTED_TO_HQ',
            'submitted_by' => $user ? $user->id : null,
            'submitted_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفع كشف درجات الدفعة للإدارة العامة وتم تجميد التعديل بالفرع آلياً.',
            'batch' => $batch,
        ]);
    }

    /**
     * HQ approves batch and applies cryptographic SHA-256 lock.
     */
    public function approveBatchByHq(Request $request, GradeBatch $batch): JsonResponse
    {
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: الاعتماد النهائي للدرجات محصور في إدارة الامتحانات المركزية.',
            ], 403);
        }

        DB::transaction(function () use ($batch, $user) {
            $grades = $batch->studentGrades()->orderBy('id')->get();

            // Compute cryptographic hash of all grades in batch
            $serializedGrades = $grades->map(fn($g) => "{$g->student_id}:{$g->course_id}:{$g->total_grade}:{$g->status}")->implode('|');
            $digitalHash = hash('sha256', "BATCH_{$batch->id}_{$serializedGrades}");

            // Lock all individual grades
            $batch->studentGrades()->update([
                'is_locked' => true,
                'locked_at' => Carbon::now(),
            ]);

            $batch->update([
                'status' => 'HQ_APPROVED',
                'approved_by' => $user ? $user->id : null,
                'approved_at' => Carbon::now(),
                'batch_digital_hash' => $digitalHash,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'تم الاعتماد النهائي لكشف الدرجات وإقفال الدفعة وتوليد البصمة الرقمية الموثقة.',
            'batch' => $batch,
        ]);
    }
}
