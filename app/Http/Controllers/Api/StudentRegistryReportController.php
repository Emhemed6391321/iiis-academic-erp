<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\Branch;
use App\Models\StudyYear;
use App\Models\Department;
use App\Models\AcademicYear;
use App\Models\StudentGrade;
use App\Models\StudentAttendance;
use App\Models\StudentBehavior;
use App\Models\ExcuseRequest;
use App\Models\StudentTransfer;
use App\Models\StudentStatusHistory;
use App\Models\EnrollmentStatusRequest;
use App\Models\StudentDocument;
use App\Models\SystemAuditTrail;

class StudentRegistryReportController extends Controller
{
    /**
     * Space-joined concatenation that works on SQLite/PostgreSQL (||) and MySQL/MariaDB (CONCAT_WS).
     */
    private static function concatSql(array $columns): string
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return "CONCAT_WS(' ', " . implode(', ', $columns) . ')';
        }

        return implode(" || ' ' || ", $columns);
    }

    /**
     * Filtered registry query shared by the paginated list and the CSV export.
     * Returns the query and the number of archived rows matching the other filters.
     */
    private function buildRegistryQuery(Request $request): array
    {
        $query = Student::withoutGlobalScopes()
            ->with([
                'branch:id,name,gender',
                'department:id,name,code',
                'currentStudyYear:id,name,level_order',
                'enrolledAcademicYear:id,name,code',
                'approver:id,name',
            ]);

        // عزل الفروع: حصر مستخدمي الفروع ببيانات فرعهم ما لم يكن المستخدم يملك صلاحية مركزية
        $user = Auth::user();
        if ($user && !$user->hasGlobalAccessScope()) {
            $query->where('branch_id', $user->branch_id ?? -1);
        } else {
            // تصفية بالفرع لمستخدمي الإدارة العامة
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $query->where('branch_id', $request->branch_id);
            }
        }

        // 1. بحث نصي شامل متعدد الحقول
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('father_name', 'like', "%{$s}%")
                  ->orWhere('grandfather_name', 'like', "%{$s}%")
                  ->orWhere('family_name', 'like', "%{$s}%")
                  ->orWhereRaw(self::concatSql(['first_name', 'father_name', 'grandfather_name', 'family_name']) . ' like ?', ["%{$s}%"])
                  ->orWhereRaw(self::concatSql(['first_name', 'family_name']) . ' like ?', ["%{$s}%"])
                  ->orWhere('national_id', 'like', "%{$s}%")
                  ->orWhere('academic_number', 'like', "%{$s}%")
                  ->orWhere('ministry_student_id', 'like', "%{$s}%")
                  ->orWhere('passport_number', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('guardian_phone', 'like', "%{$s}%");
            });
        }

        // 3. تصفية بالمرحلة الدراسية
        if ($request->filled('study_year_id') && $request->study_year_id !== 'all') {
            $query->where('current_study_year_id', $request->study_year_id);
        }

        // 4. تصفية بالشعبة / القسم
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $query->where('department_id', $request->department_id);
        }

        // 5. تصفية بصفة القيد (نظامي / انتساب)
        if ($request->filled('study_type') && $request->study_type !== 'all') {
            $query->where('study_type', $request->study_type);
        }

        // 6. تصفية بحالة القيد الأكاديمية
        if ($request->filled('academic_status') && $request->academic_status !== 'all') {
            $query->where('academic_status', $request->academic_status);
        }

        // 7. تصفية بالعام الدراسي المقيد به
        if ($request->filled('academic_year_id') && $request->academic_year_id !== 'all') {
            $query->where('enrolled_academic_year_id', $request->academic_year_id);
        }

        // 8. تصفية بالجنس (ذكر / أنثى)
        if ($request->filled('gender') && $request->gender !== 'all') {
            $query->where('gender', $request->gender);
        }

        // 9. تصفية بالاحتياجات الخاصة أو الإعاقة
        if ($request->filled('has_disability') && $request->has_disability !== 'all') {
            $query->where('has_disability', filter_var($request->has_disability, FILTER_VALIDATE_BOOLEAN));
        }

        // 9b. حساب عدد المؤرشفين استناداً لمعايير البحث الأخرى
        $archivedCount = (clone $query)->where('is_archived', true)->count();

        // 9c. تصفية بالأرشفة (مؤرشف / غير مؤرشف)
        if ($request->filled('is_archived') && $request->is_archived !== 'all') {
            $query->where('is_archived', filter_var($request->is_archived, FILTER_VALIDATE_BOOLEAN));
        } elseif ($request->get('tab') === 'archived') {
            $query->where('is_archived', true);
        } elseif (!$request->filled('is_archived') && !$request->filled('search')) {
            $query->where('is_archived', false);
        }

        return [$query, $archivedCount];
    }

    /**
     * سجل الطلاب العام المركزي - تصفية متقدمة، تقسيم صفحات 50/صفحة، إحصائيات شاملة
     */
    public function index(Request $request): JsonResponse
    {
        [$query, $archivedCount] = $this->buildRegistryQuery($request);
        $user = Auth::user();

        // حساب الإحصائيات التجميعية للاستعلام الحالي
        $totalCount = (clone $query)->count();
        $maleCount = (clone $query)->where('gender', 'MALE')->count();
        $femaleCount = (clone $query)->where('gender', 'FEMALE')->count();
        $regularCount = (clone $query)->where('study_type', 'REGULAR')->count();
        $intisabCount = (clone $query)->where('study_type', 'INTISAB')->count();
        $activeCount = (clone $query)->whereIn('academic_status', ['ENROLLED_ACTIVE', 'ACTIVE', 'REGULAR'])->count();
        $suspendedCount = (clone $query)->where('academic_status', 'SUSPENDED')->count();
        $transferredCount = (clone $query)->where('academic_status', 'TRANSFERRED')->count();
        $graduatedCount = (clone $query)->where('academic_status', 'GRADUATED')->count();

        // 10. الفرز (Sorting)
        $sortBy = $request->get('sort_by', 'id');
        $sortDir = strtolower($request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'id', 'academic_number', 'national_id', 'first_name', 'family_name', 
            'birth_date', 'gender', 'study_type', 'academic_status', 'created_at',
            'branch_id', 'current_study_year_id', 'department_id'
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('id', 'desc');
        }

        // 11. الترقيم والتجزئة (50 سجل في الصفحة كمعيار أساسي)
        $perPage = min(200, max(1, (int) $request->get('per_page', 50)));
        if ($perPage <= 0) {
            $perPage = 50;
        }

        if ($request->get('all') === 'true' || $perPage > 500) {
            $students = $query->get();
            $paginationMeta = [
                'current_page' => 1,
                'per_page'     => $students->count(),
                'total'        => $students->count(),
                'last_page'    => 1,
                'from'         => 1,
                'to'           => $students->count(),
            ];
        } else {
            $paginator = $query->paginate($perPage);
            $students = $paginator->items();
            $paginationMeta = [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $students,
            'pagination' => $paginationMeta,
            'stats' => [
                'total'       => $totalCount,
                'male'        => $maleCount,
                'female'      => $femaleCount,
                'regular'     => $regularCount,
                'intisab'     => $intisabCount,
                'active'      => $activeCount,
                'suspended'   => $suspendedCount,
                'transferred' => $transferredCount,
                'graduated'   => $graduatedCount,
                'archived'    => $archivedCount,
            ],
            'filters_data' => [
                'branches'       => (function() use ($user) {
                    $bq = Branch::where('is_active', true)
                        ->whereNotIn('branch_status', ['CLOSED', 'SUSPENDED', 'CANCELED']);
                    if ($user && $user->branch_id && !$user->hasGlobalAccessScope()) {
                        $bq->where('id', $user->branch_id);
                    }
                    return $bq->orderBy('name')->get(['id', 'name', 'code', 'gender']);
                })(),
                'study_years'    => StudyYear::orderBy('level_order')->get(['id', 'name', 'level_order']),
                'departments'    => Department::where('is_active', true)->get(['id', 'name', 'code']),
                'academic_years' => AcademicYear::orderBy('id', 'desc')->get(['id', 'name', 'code', 'is_current']),
            ],
            'current_user' => [
                'name' => $user?->name ?? 'مسؤول المنظومة',
                'role' => $user?->role ?? 'مسجل عام',
                'branch' => $user?->branch?->name ?? 'الإدارة العامة',
            ],
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * خدمة استخراج وطباعة «تعريف طالب» رسمي
     */
    public function getEnrollmentCertificate(Student $student): JsonResponse
    {
        $student->load([
            'branch',
            'department',
            'currentStudyYear',
            'enrolledAcademicYear',
            'approver',
        ]);

        $user = Auth::user();
        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYearName = $student->enrolledAcademicYear?->name ?? ($currentYear?->name ?? '2026/2027');

        $birthDateFormatted = $student->birth_date ? Carbon::parse($student->birth_date)->format('Y/m/d') : '—';
        $birthPlace = $student->birth_place ?: 'ليبيا';
        $branchName = $student->branch?->name ?: 'الفرع الرئيسي';
        $stageName = $student->currentStudyYear?->name ?: 'السنة الأولى';
        $sectionName = $student->department?->name ?: 'شعبة الدراسات الإسلامية';
        $studyType = $student->study_type === 'INTISAB' ? 'انتساب' : 'نظامي';
        $academicNumber = $student->academic_number ?: ('قيد الاعتماد (' . $student->id . ')');
        $nationalId = $student->national_id;
        $nationality = $student->nationality ?: 'ليبي';

        $profile = \App\Services\AdminSettingsService::getInstituteProfile();
        $instituteName = $profile['institute_name'];
        $supervisingBody = $profile['supervising_body'];
        $departmentBody = $profile['supervising_department'];
        $signatories = \App\Services\AdminSettingsService::getSignatoriesFor('enrollment_cert', $student->branch_id);

        // إنشاء أو استرجاع قيد التوثيق الرقمي المؤمن بالسلسلة المشفرة
        $ledgerService = app(\App\Services\DocumentLedgerService::class);
        $verification = \App\Models\DocumentVerification::where('student_id', $student->id)
            ->where('document_type', 'ENROLLMENT_CERTIFICATE')
            ->where('status', 'VALID')
            ->latest('id')
            ->first();

        if (!$verification) {
            $verification = $ledgerService->issueDocument(
                'ENROLLMENT_CERTIFICATE',
                $student,
                [
                    'academic_year' => $academicYearName,
                    'stage'         => $stageName,
                    'section'       => $sectionName,
                    'study_type'    => $studyType,
                ],
                'مدير عام المعهد التخصصي للعلوم الشرعية',
                $user?->id
            );
        }

        $refNumber = 'تعريف-' . date('Y') . '-' . substr($verification->document_uuid, 0, 8);
        $qrCodeUrl = url("/verify/doc/{$verification->document_uuid}");

        // النص الرسمي المعتمد لتعريف الطالب
        $officialText = "تشهد إدارة {$instituteName} – فرع ({$branchName}) بأن الطالب: ({$student->full_name})، مواليد ({$birthPlace} - {$birthDateFormatted})، جنسيته ({$nationality})، ورقمه الوطني ({$nationalId})، المقيد بالمعهد بالمرحلة الدراسية ({$stageName})، بشعبة ({$sectionName})، وبصفة قيد ({$studyType})، تحت رقم قيد ({$academicNumber})، وذلك عن العام الدراسي ({$academicYearName}). وقد أُعطي له هذا التعريف بناءً على طلبه لتقديمه إلى الجهات ذات العلاقة دون أدنى مسؤولية أو التزام مالي على المعهد.";

        $certificatePayload = [
            'institute_name'     => $instituteName,
            'supervising_body'   => $supervisingBody,
            'department_body'    => $departmentBody,
            'logo_url'           => $profile['logo_url'],
            'stamp_url'          => $profile['stamp_url'],
            'certificate_type'   => 'تعريف طالب مقيد',
            'ref_number'         => $refNumber,
            'document_uuid'      => $verification->document_uuid,
            'issued_date'        => Carbon::now()->format('Y/m/d'),
            'student'            => [
                'id'                 => $student->id,
                'full_name'          => $student->full_name,
                'academic_number'    => $academicNumber,
                'national_id'        => $nationalId,
                'nationality'        => $nationality,
                'gender'             => $student->gender === 'MALE' ? 'ذكر' : 'أنثى',
                'birth_date'         => $birthDateFormatted,
                'birth_place'        => $birthPlace,
                'branch_name'        => $branchName,
                'stage_name'         => $stageName,
                'section_name'       => $sectionName,
                'study_type'         => $studyType,
                'academic_status'    => $student->status_label,
                'academic_year'      => $academicYearName,
                'photo_url'          => $student->profile_photo_path ? Storage::url($student->profile_photo_path) : null,
                'phone'              => $student->phone,
                'guardian_phone'     => $student->guardian_phone,
            ],
            'official_text'      => $officialText,
            'issuer'             => [
                'name'               => $user?->name ?? 'مسؤول التسجيل وشؤون الطلاب',
                'role'               => $user?->role ?? 'مسجل عام المعهد',
                'branch'             => $user?->branch?->name ?? $branchName,
                'timestamp'          => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            'signatories'        => $signatories,
            'qr_verification_code' => $qrCodeUrl,
        ];

        return response()->json([
            'success' => true,
            'certificate' => $certificatePayload,
            'data' => $certificatePayload,
        ]);
    }

    /**
     * خدمة استخراج وطباعة «شهادة حسن سيرة وسلوك» رسمية
     */
    public function getGoodConductCertificate(Student $student): JsonResponse
    {
        $student->load([
            'branch',
            'department',
            'currentStudyYear',
            'enrolledAcademicYear',
            'behaviors',
        ]);

        $user = Auth::user();
        $currentYear = AcademicYear::where('is_current', true)->first();
        $academicYearName = $student->enrolledAcademicYear?->name ?? ($currentYear?->name ?? '2026/2027');

        $branchName = $student->branch?->name ?: 'الفرع الرئيسي';
        $stageName = $student->currentStudyYear?->name ?: 'السنة الأولى';
        $sectionName = $student->department?->name ?: 'شعبة الدراسات الإسلامية';
        $studyType = $student->study_type === 'INTISAB' ? 'انتساب' : 'نظامي';
        $academicNumber = $student->academic_number ?: ('قيد الاعتماد (' . $student->id . ')');
        $nationalId = $student->national_id;

        // التحقق من سجل السلوكيات
        $severeViolationsCount = $student->behaviors->whereIn('warning_level', ['LEVEL_3', 'LEVEL_4', 'EXPULSION'])->count();
        $hasCleanRecord = ($severeViolationsCount === 0);

        $profile = \App\Services\AdminSettingsService::getInstituteProfile();
        $instituteName = $profile['institute_name'];
        $supervisingBody = $profile['supervising_body'];
        $departmentBody = $profile['supervising_department'];
        $signatories = \App\Services\AdminSettingsService::getSignatoriesFor('conduct_cert', $student->branch_id);

        // إنشاء أو استرجاع قيد التوثيق الرقمي المؤمن بالسلسلة المشفرة
        $ledgerService = app(\App\Services\DocumentLedgerService::class);
        $verification = \App\Models\DocumentVerification::where('student_id', $student->id)
            ->where('document_type', 'GOOD_CONDUCT_CERTIFICATE')
            ->where('status', 'VALID')
            ->latest('id')
            ->first();

        if (!$verification) {
            $verification = $ledgerService->issueDocument(
                'GOOD_CONDUCT_CERTIFICATE',
                $student,
                [
                    'academic_year' => $academicYearName,
                    'stage'         => $stageName,
                    'section'       => $sectionName,
                    'study_type'    => $studyType,
                    'clean_record'  => $hasCleanRecord,
                ],
                'مدير عام المعهد التخصصي للعلوم الشرعية',
                $user?->id
            );
        }

        $refNumber = 'سلوك-' . date('Y') . '-' . substr($verification->document_uuid, 0, 8);
        $qrCodeUrl = url("/verify/doc/{$verification->document_uuid}");

        // النص الرسمي المعتمد لشهادة حسن السيرة والسلوك
        $officialText = "تشهد إدارة {$instituteName} – فرع ({$branchName}) بأن الطالب: ({$student->full_name})، ورقمه الوطني ({$nationalId})، ورقم قيده الأكاديمي ({$academicNumber})، المقيد بالمعهد بالمرحلة الدراسية ({$stageName}) بشعبة ({$sectionName}) وبصفة قيد ({$studyType}) عن العام الدراسي ({$academicYearName})، كان خلال فترة دراسته بالمعهد مثالاً للطالب الملتزم، وحسن السيرة والسلوك، ولم يصدر منه طيلة فترة قيده ما يخل بأنظمة المعهد وقوانينه ولوائحه المعمول بها. وقد مُنح هذه الشهادة بناءً على طلبه لاستعمالها فيما يسمح به القانون دون أدنى مسؤولية على المعهد.";

        $certificatePayload = [
            'institute_name'     => $instituteName,
            'supervising_body'   => $supervisingBody,
            'department_body'    => $departmentBody,
            'logo_url'           => $profile['logo_url'],
            'stamp_url'          => $profile['stamp_url'],
            'certificate_type'   => 'شهادة حسن سيرة وسلوك',
            'ref_number'         => $refNumber,
            'document_uuid'      => $verification->document_uuid,
            'issued_date'        => Carbon::now()->format('Y/m/d'),
            'student'            => [
                'id'                 => $student->id,
                'full_name'          => $student->full_name,
                'academic_number'    => $academicNumber,
                'national_id'        => $nationalId,
                'nationality'        => $student->nationality ?: 'ليبي',
                'branch_name'        => $branchName,
                'stage_name'         => $stageName,
                'section_name'       => $sectionName,
                'study_type'         => $studyType,
                'academic_status'    => $student->status_label,
                'academic_year'      => $academicYearName,
                'photo_url'          => $student->profile_photo_path ? Storage::url($student->profile_photo_path) : null,
            ],
            'official_text'      => $officialText,
            'clean_record'       => $hasCleanRecord,
            'issuer'             => [
                'name'               => $user?->name ?? 'مسؤول شؤون الطلاب والامتحانات',
                'role'               => $user?->role ?? 'مسجل عام المعهد',
                'branch'             => $user?->branch?->name ?? $branchName,
                'timestamp'          => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            'signatories'        => $signatories,
            'qr_verification_code' => $qrCodeUrl,
        ];

        return response()->json([
            'success' => true,
            'certificate' => $certificatePayload,
            'data' => $certificatePayload,
        ]);
    }

    /**
     * خدمة «التقرير التفصيلي للطالب – سري» (Confidential Detailed Dossier)
     * يتضمن كامل البيانات (360 درجة) مع وسم "سري" وتسجيل الوصول في سجل التدقيق
     */
    public function getConfidentialReport(Student $student): JsonResponse
    {
        $user = Auth::user();

        // 1. تسجيل الوصول في سجل التدقيق الأمني الموثق بالسلسلة الرقمية
        SystemAuditTrail::log(
            eventType: 'CONFIDENTIAL_REPORT_ACCESSED',
            modelType: Student::class,
            modelId: $student->id,
            description: "اطلاع على التقرير التفصيلي السري للطالب: {$student->full_name} ({$student->academic_number}).",
            branchId: $student->branch_id,
            newValues: [
                'student_id'      => $student->id,
                'academic_number' => $student->academic_number,
                'national_id'     => $student->national_id,
            ],
            severity: 'WARNING'
        );

        // 2. تحميل كافة العلاقات المرتبطة بملف الطالب
        $student->load([
            'branch',
            'department',
            'currentStudyYear',
            'enrolledAcademicYear',
            'approver',
            'dataVerifier',
            'documents',
            'transfers.fromBranch',
            'transfers.toBranch',
            'transfers.approver',
            'notes.author',
            'behaviors.logger',
            'excuses.reviewer',
            'attendance',
            'statusHistory.changedBy',
            'enrollmentStatusRequests.creator',
            'grades.course',
        ]);

        // إحصائيات الحضور والغياب
        $attendanceRecords = $student->attendance;
        $attendanceStats = [
            'PRESENT' => $attendanceRecords->where('status', 'PRESENT')->count(),
            'ABSENT'  => $attendanceRecords->where('status', 'ABSENT')->count(),
            'LATE'    => $attendanceRecords->where('status', 'LATE')->count(),
            'EXCUSED' => $attendanceRecords->where('status', 'EXCUSED')->count(),
            'TOTAL'   => $attendanceRecords->count(),
        ];
        $attendanceStats['RATE'] = $attendanceStats['TOTAL'] > 0 
            ? round(($attendanceStats['PRESENT'] / $attendanceStats['TOTAL']) * 100, 1) 
            : 100;

        // التحقق من صلاحيات الاطلاع على الدرجات وكشوف الامتحانات (RBAC Gate)
        $roleName = strtolower($user?->role?->name ?? '');
        $canViewControlGrades = $user && (
            $user->isSuperAdmin() || 
            $user->hasPermission('grades.view') || 
            in_array($roleName, ['super_admin', 'hq_exams_director', 'branch_control_officer', 'control_officer', 'exam_director', 'exams_officer'])
        );

        // ملخص الدرجات والنتائج الأكاديمية (محجوب إذا لم تتوفر الصلاحية)
        if ($canViewControlGrades) {
            $grades = $student->grades->map(function ($g) {
                return [
                    'course_code'      => $g->course?->code ?? '—',
                    'course_name'      => $g->course?->name ?? '—',
                    'semester'         => $g->course?->semester ?? 1,
                    'coursework_grade' => $g->coursework_grade,
                    'midterm_grade'    => $g->midterm_grade,
                    'final_grade'      => $g->final_grade,
                    'total_grade'      => $g->total_grade,
                    'is_passed'        => $g->is_passed,
                    'notes'            => $g->notes,
                ];
            });
            $gpaTotal = $grades->whereNotNull('total_grade')->avg('total_grade');
        } else {
            $grades = collect([]);
            $gpaTotal = null;
        }

        // ملخص الوثائق والمستندات
        $documentsList = $student->documents->map(function ($doc) {
            return [
                'id'            => $doc->id,
                'type'          => $doc->document_type,
                'file_name'     => $doc->file_name ?? basename($doc->file_path),
                'file_url'      => $doc->file_path ? Storage::url($doc->file_path) : null,
                'is_verified'   => $doc->is_verified ?? true,
                'uploaded_at'   => $doc->created_at?->format('Y-m-d H:i'),
            ];
        });

        $profile = \App\Services\AdminSettingsService::getInstituteProfile();
        $signatories = \App\Services\AdminSettingsService::getSignatoriesFor('secret_report', $student->branch_id);

        // تجميع التقرير السري الكامل
        $reportPayload = [
            'can_view_grades' => $canViewControlGrades,
            'header' => [
                'classification'    => 'سري للغاية وخاص بإدارة المعهد',
                'institute_name'    => $profile['institute_name'],
                'supervising_body'  => $profile['supervising_body'],
                'department_body'   => $profile['supervising_department'],
                'logo_url'          => $profile['logo_url'],
                'stamp_url'         => $profile['stamp_url'],
                'report_title'      => 'التقرير التفصيلي الشامل لملف الطالب',
                'report_code'       => 'CONF-DOSSIER-' . date('Y') . '-' . str_pad($student->id, 6, '0', STR_PAD_LEFT),
                'extracted_at'      => Carbon::now()->format('Y-m-d H:i:s'),
                'extracted_by'      => $user?->name ?? 'مسؤول النظام المركزي',
                'extracted_by_role' => $user?->role ?? 'مسجل عام',
                'ip_address'        => request()->ip(),
            ],
            'signatories' => $signatories,
            'personal_data' => [
                'full_name'             => $student->full_name,
                'first_name'            => $student->first_name,
                'father_name'           => $student->father_name,
                'grandfather_name'      => $student->grandfather_name,
                'family_name'           => $student->family_name,
                'mother_name'           => $student->mother_name,
                'national_id'           => $student->national_id,
                'ministry_student_id'   => $student->ministry_student_id ?: '—',
                'passport_number'       => $student->passport_number ?: '—',
                'birth_date'            => $student->birth_date?->format('Y-m-d') ?: '—',
                'birth_place'           => $student->birth_place ?: '—',
                'age'                   => $student->age ?: '—',
                'gender'                => $student->gender === 'MALE' ? 'ذكر' : 'أنثى',
                'nationality'           => $student->nationality ?: 'ليبي',
                'religion'              => $student->religion ?: 'مسلم',
                'phone'                 => $student->phone ?: '—',
                'guardian_phone'        => $student->guardian_phone ?: '—',
                'guardian_name'         => $student->guardian_name ?: ($student->father_name . ' ' . $student->family_name),
                'guardian_relationship' => $student->guardian_relationship ?: 'الأب',
                'address'               => $student->address ?: '—',
                'emergency_contact'     => $student->emergency_contact ?: '—',
                'bus_route'             => $student->bus_route ?: '—',
                'profile_photo_url'     => $student->profile_photo_path ? Storage::url($student->profile_photo_path) : null,
            ],
            'academic_data' => [
                'academic_number'       => $student->academic_number ?: 'قيد الاعتماد',
                'branch_name'           => $student->branch?->name ?: 'الفرع الرئيسي',
                'department_name'       => $student->department?->name ?: 'شعبة الدراسات الإسلامية',
                'study_year_name'       => $student->currentStudyYear?->name ?: 'السنة الأولى',
                'enrolled_year_name'    => $student->enrolledAcademicYear?->name ?: '2026/2027',
                'study_type'            => $student->study_type_label,
                'academic_status'       => $student->status_label,
                'registration_type'     => $student->registration_type ?: 'نظامي جديد',
                'previous_school'       => $student->previous_school ?: '—',
                'previous_level'        => $student->previous_level ?: 'الشهادة الإعدادية',
                'approved_by_name'      => $student->approver?->name ?: 'التسجيل المركزي',
                'approved_at'           => $student->approved_at?->format('Y-m-d H:i') ?: '—',
                'data_verified_by'      => $student->dataVerifier?->name ?: 'مسؤول التدقيق',
                'data_verified_at'      => $student->data_verified_at?->format('Y-m-d H:i') ?: '—',
                'admin_notes'           => $student->notes ?: 'لا توجد ملاحظات إدارية مسجلة',
            ],
            'health_and_disability' => [
                'has_disability'        => (bool)$student->has_disability,
                'disability_type'       => $student->disability_type ?: '—',
                'disability_details'    => $student->disability_details ?: '—',
                'is_special_needs'      => (bool)$student->is_special_needs,
                'special_needs_desc'    => $student->special_needs_desc ?: '—',
                'blood_type'            => $student->blood_type ?: '—',
                'health_status'         => $student->health_status ?: 'سليم ولائق صحياً',
                'chronic_diseases'      => $student->chronic_diseases ?: 'لا يوجد',
                'allergies'             => $student->allergies ?: 'لا يوجد',
            ],
            'grades_summary' => [
                'courses_count'         => $grades->count(),
                'average_score'         => $gpaTotal ? round($gpaTotal, 2) : '—',
                'records'               => $grades,
            ],
            'attendance_summary'        => $attendanceStats,
            'recent_attendance'         => $student->attendance->take(15)->map(fn($a) => [
                'date'   => $a->record_date?->format('Y-m-d') ?? $a->record_date,
                'status' => $a->status,
                'reason' => $a->notes,
            ]),
            'behaviors_record'          => $student->behaviors->map(fn($b) => [
                'date'          => $b->violation_date?->format('Y-m-d') ?? $b->violation_date,
                'type'          => $b->violation_type,
                'level'         => $b->warning_level,
                'description'   => $b->description,
                'action_taken'  => $b->action_taken,
                'recorded_by'   => $b->logger?->name ?? 'مشرف النظام',
            ]),
            'excuses_record'            => $student->excuses->map(fn($e) => [
                'start_date'    => $e->start_date?->format('Y-m-d') ?? $e->start_date,
                'end_date'      => $e->end_date?->format('Y-m-d') ?? $e->end_date,
                'reason'        => $e->reason,
                'status'        => $e->status,
                'reviewer'      => $e->reviewer?->name ?? '—',
            ]),
            'transfers_record'          => $student->transfers->map(fn($t) => [
                'from_branch'   => $t->fromBranch?->name ?? '—',
                'to_branch'     => $t->toBranch?->name ?? '—',
                'transfer_date' => $t->created_at?->format('Y-m-d'),
                'reason'        => $t->reason,
                'status'        => $t->status,
                'approved_by'   => $t->approver?->name ?? '—',
            ]),
            'status_history_record'     => $student->statusHistory->map(fn($h) => [
                'date'          => $h->event_date?->format('Y-m-d') ?? $h->created_at?->format('Y-m-d'),
                'event_type'    => $h->event_type,
                'old_status'    => $h->old_status,
                'new_status'    => $h->new_status,
                'reason'        => $h->reason,
                'changed_by'    => $h->changedBy?->name ?? '—',
            ]),
            'documents_archive'         => $documentsList,
            'administrative_notes'      => $student->notes()->with('author:id,name')->get()->map(fn($n) => [
                'note'       => $n->note_text ?? $n->content ?? '',
                'by'         => $n->author?->name ?? 'إدارة المعهد',
                'created_at' => $n->created_at?->format('Y-m-d H:i'),
            ]),
        ];

        return response()->json([
            'success' => true,
            'report' => $reportPayload,
            'data' => $reportPayload,
        ]);
    }

    /**
     * تصدير السجل المخصص إلى ملف CSV
     */
    public function exportRegistry(Request $request)
    {
        // An export covers every matching student, streamed in chunks (not just the first page).
        [$query] = $this->buildRegistryQuery($request);
        $totalRows = (clone $query)->count();
        $columns = $request->get('columns', 'academic_number,full_name,national_id,branch,stage,section,study_type,academic_status,birth_date,phone');
        // The UI sends a JSON array, API clients may send a comma list.
        $decoded = is_string($columns) ? json_decode($columns, true) : $columns;
        $colList = is_array($decoded) ? array_map('strval', $decoded) : explode(',', (string) $columns);
        $allowedColumns = ['academic_number', 'full_name', 'national_id', 'gender', 'birth_date', 'birth_place', 'nationality', 'branch', 'stage', 'section', 'study_type', 'academic_status', 'academic_year', 'phone', 'guardian_phone', 'address'];
        $colList = array_values(array_intersect(array_map('trim', $colList), $allowedColumns)) ?: ['academic_number', 'full_name', 'national_id', 'branch', 'stage', 'section'];
        $columns = implode(',', $colList);

        $user = Auth::user();
        SystemAuditTrail::log(
            eventType: 'STUDENT_REGISTRY_EXPORTED',
            modelType: Student::class,
            description: "تصدير سجل قيد الطلاب إلى ملف CSV (" . $totalRows . " سجل).",
            branchId: $user?->branch_id,
            newValues: [
                'columns' => $columns,
                'count' => $totalRows,
                'filters' => $request->only(['branch_id', 'stage_id', 'department_id', 'status', 'study_type']),
            ],
            severity: 'WARNING',
            request: $request
        );

        $filename = 'students_registry_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query, $colList) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel Arabic support
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header mapping
            $headerMap = [
                'academic_number' => 'الرقم الأكاديمي',
                'full_name'       => 'اسم الطالب الرباعي',
                'national_id'     => 'الرقم الوطني',
                'gender'          => 'الجنس',
                'birth_date'      => 'تاريخ الميلاد',
                'birth_place'     => 'مكان الميلاد',
                'nationality'     => 'الجنسية',
                'branch'          => 'الفرع التعليمي',
                'stage'           => 'المرحلة الدراسية',
                'section'         => 'الشعبة / القسم',
                'study_type'      => 'صفة القيد',
                'academic_status' => 'حالة القيد',
                'academic_year'   => 'العام الأكاديمي',
                'phone'           => 'هاتف الطالب',
                'guardian_phone'  => 'هاتف ولي الأمر',
                'address'         => 'العنوان',
            ];

            $csvHeaders = [];
            foreach ($colList as $col) {
                $csvHeaders[] = $headerMap[$col] ?? $col;
            }
            fputcsv($handle, $csvHeaders);

            // Neutralise spreadsheet formula injection (=, +, -, @, tab, CR at the start of a cell).
            $safeCell = static fn ($v) => is_string($v) && $v !== '' && strpbrk($v[0], "=+-@	") !== false ? "'" . $v : $v;

            $query->chunkById(500, function ($chunk) use ($handle, $colList, $safeCell) {
            foreach ($chunk as $model) {
                // Build the row from raw attributes: toArray() runs Carbon for every date/age accessor
                // (~1ms per student) and none of that is exported.
                $st = $model->getAttributes();
                $st['full_name'] = $model->full_name;
                $st['status_label'] = $model->status_label;
                $st['birth_date'] = substr((string) ($st['birth_date'] ?? ''), 0, 10);
                $st['branch'] = ['name' => $model->branch?->name];
                $st['current_study_year'] = ['name' => $model->currentStudyYear?->name];
                $st['department'] = ['name' => $model->department?->name];
                $st['enrolled_academic_year'] = ['name' => $model->enrolledAcademicYear?->name];
                $row = [];
                foreach ($colList as $col) {
                    $row[] = match($col) {
                        'academic_number' => $st['academic_number'] ?? '',
                        'full_name'       => $st['full_name'] ?? trim(($st['first_name'] ?? '') . ' ' . ($st['father_name'] ?? '') . ' ' . ($st['family_name'] ?? '')),
                        'national_id'     => $st['national_id'] ?? '',
                        'gender'          => match ($st['gender'] ?? '') { 'MALE' => 'ذكر', 'FEMALE' => 'أنثى', default => '' },
                        'birth_date'      => $st['birth_date'] ?? '',
                        'birth_place'     => $st['birth_place'] ?? '',
                        'nationality'     => $st['nationality'] ?? 'ليبي',
                        'branch'          => $st['branch']['name'] ?? '—',
                        'stage'           => $st['current_study_year']['name'] ?? 'السنة الأولى',
                        'section'         => $st['department']['name'] ?? 'شعبة الدراسات الإسلامية',
                        'study_type'      => ($st['study_type'] ?? '') === 'INTISAB' ? 'انتساب' : 'نظامي',
                        'academic_status' => $st['status_label'] ?? ($st['academic_status'] ?? ''),
                        'academic_year'   => $st['enrolled_academic_year']['name'] ?? '',
                        'phone'           => $st['phone'] ?? '',
                        'guardian_phone'  => $st['guardian_phone'] ?? '',
                        'address'         => $st['address'] ?? '',
                        default           => $st[$col] ?? '',
                    };
                }
                fputcsv($handle, array_map($safeCell, $row));
            }
            });
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
