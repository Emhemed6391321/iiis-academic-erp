<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\StudentBehavior;
use App\Models\ExcuseRequest;
use App\Models\StudentStatusHistory;
use App\Models\EnrollmentStatusRequest;
use App\Models\StudentAttendance;
use App\Models\StudentDocument;
use App\Models\Branch;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\StudyYear;
use App\Models\SystemAuditTrail;
use App\Services\StudentStateMachineService;
use App\Services\SecureFileVaultService;

class StudentFileController extends Controller
{
    // ===================================================================
    // SHOW — جلب الملف الكامل للطالب
    // ===================================================================

    public function show(Student $student): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->cannot('view', $student)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: لا يمكنك الاطلاع على ملف طالب يتبع فرعاً تعليمياً آخر.',
            ], 403);
        }

        SystemAuditTrail::log(
            eventType: 'STUDENT_FILE_VIEWED',
            modelType: Student::class,
            modelId: $student->id,
            description: "عرض الملف الأكاديمي الشامل للطالب: {$student->full_name} ({$student->academic_number}).",
            branchId: $student->branch_id,
            severity: 'INFO'
        );

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
        ]);

        // إحصائيات الحضور
        $attendanceStats = StudentAttendance::where('student_id', $student->id)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // عدد المخالفات حسب المستوى
        $behaviorCounts = StudentBehavior::where('student_id', $student->id)
            ->selectRaw("warning_level, COUNT(*) as count")
            ->groupBy('warning_level')
            ->pluck('count', 'warning_level')
            ->toArray();

        // المستندات مُصنَّفة
        $documentsByType = $student->documents->groupBy('document_type');

        return response()->json([
            'success' => true,
            'student' => $student,
            'stats' => [
                'attendance' => $attendanceStats,
                'behaviors'  => $behaviorCounts,
                'docs_count' => $student->documents->count(),
            ],
            'documents_by_type' => $documentsByType,
            // بيانات مساعدة للـ dropdowns
            'meta' => [
                'academic_years' => AcademicYear::orderBy('id', 'desc')->get(['id', 'name']),
                'branches'       => Branch::where('is_active', true)->get(['id', 'name', 'gender_type as gender']),
                'departments'    => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'study_years'    => StudyYear::orderBy('level_order')->get(['id', 'name', 'level_order']),
            ],
        ]);
    }

    // ===================================================================
    // TIMELINE — السجل الموحد للأحداث
    // ===================================================================

    public function getTimeline(Student $student): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->cannot('view', $student)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: لا يمكنك الاطلاع على سجل أحداث طالب يتبع فرعاً تعليمياً آخر.',
            ], 403);
        }

        $timeline = StudentStatusHistory::where('student_id', $student->id)
            ->with('changedBy:id,name')
            ->orderBy('event_date', 'desc')
            ->get()
            ->map(fn($h) => [
                'id'       => $h->id,
                'date'     => $h->event_date?->format('Y-m-d'),
                'type'     => $h->event_type,
                'meta'     => $h->timeline_meta,
                'old'      => $h->old_status,
                'new'      => $h->new_status,
                'reason'   => $h->reason,
                'doc_path' => $h->document_path ? Storage::url($h->document_path) : null,
                'by'       => $h->changedBy?->name ?? 'غير محدد',
                'extra'    => $h->meta,
            ]);

        return response()->json(['success' => true, 'timeline' => $timeline]);
    }

    // ===================================================================
    // NOTES — الملاحظات
    // ===================================================================

    public function getNotes(Student $student): JsonResponse
    {
        $notes = StudentNote::where('student_id', $student->id)
            ->with(['author:id,name', 'replier:id,name'])
            ->latest()
            ->get();
        return response()->json(['success' => true, 'notes' => $notes]);
    }

    public function addNote(Request $request, Student $student): JsonResponse
    {
        $request->validate(['note_text' => 'required|string|min:3|max:2000']);
        $note = StudentNote::create([
            'student_id' => $student->id,
            'note_text'  => $request->note_text,
            'created_by' => Auth::id(),
        ]);
        return response()->json(['success' => true, 'message' => 'تم تدوين الملاحظة بنجاح.', 'note' => $note->load('author')]);
    }

    // ===================================================================
    // BEHAVIORS — السلوكيات
    // ===================================================================

    public function getBehaviors(Student $student): JsonResponse
    {
        $behaviors = StudentBehavior::where('student_id', $student->id)
            ->with('logger:id,name')
            ->latest('violation_date')
            ->get();
        return response()->json(['success' => true, 'behaviors' => $behaviors]);
    }

    public function addBehavior(Request $request, Student $student): JsonResponse
    {
        $request->validate([
            'violation_type' => 'required|string|max:150',
            'warning_level'  => 'required|in:LEVEL_1,LEVEL_2,LEVEL_3',
            'description'    => 'nullable|string',
            'action_taken'   => 'nullable|string',
        ]);

        $behavior = StudentBehavior::create([
            'student_id'     => $student->id,
            'violation_type' => $request->violation_type,
            'warning_level'  => $request->warning_level,
            'description'    => $request->description,
            'action_taken'   => $request->action_taken,
            'violation_date' => now()->toDateString(),
            'logged_by'      => Auth::id(),
        ]);

        // تسجيل الحدث في السجل الموحد إذا كان المستوى الثاني أو الثالث
        if (in_array($request->warning_level, ['LEVEL_2', 'LEVEL_3'])) {
            $this->logEvent($student, 'STATUS_CHANGE', null, $student->academic_status,
                "مخالفة سلوكية - {$request->violation_type}", null,
                ['level' => $request->warning_level]
            );
        }

        $warning = '';
        if ($request->warning_level === 'LEVEL_3') {
            $warning = ' ⚠️ تحذير: هذه مخالفة من المستوى الثالث — قد تستوجب إجراءات إدارية صارمة.';
        }

        return response()->json([
            'success'  => true,
            'message'  => 'تم تسجيل المخالفة السلوكية بنجاح.' . $warning,
            'behavior' => $behavior->load('logger'),
        ]);
    }

    // ===================================================================
    // EXCUSES — الأعذار
    // ===================================================================

    public function getExcuses(Student $student): JsonResponse
    {
        $excuses = ExcuseRequest::where('student_id', $student->id)
            ->with('submitter:id,name')
            ->latest()
            ->get();
        return response()->json(['success' => true, 'excuses' => $excuses]);
    }

    public function addExcuse(Request $request, Student $student): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'reason'     => 'required|string|min:5',
            'document'   => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path = $request->file('document')->store("excuses/{$student->id}", 'public');

        $excuse = ExcuseRequest::create([
            'student_id'      => $student->id,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date ?? $request->start_date,
            'reason'          => $request->reason,
            'attachment_path' => $path,
            'status'          => 'PENDING',
            'submitted_by'    => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم طلب العذر بنجاح وهو قيد المراجعة.',
            'excuse'  => $excuse->load('submitter'),
        ]);
    }

    public function getAllExcuses(Request $request): JsonResponse
    {
        $user = Auth::user();
        $status = $request->query('status');
        $query = ExcuseRequest::with(['student.branch', 'submitter:id,name', 'reviewer:id,name']);

        // MED-5: Branch isolation — branch users see only their branch's excuses
        if ($user && !$user->hasGlobalAccessScope() && !empty($user->branch_id)) {
            $query->whereHas('student', fn($q) => $q->where('branch_id', $user->branch_id));
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $excuses = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $excuses, 'excuses' => $excuses]);
    }

    public function reviewExcuse(Request $request, ExcuseRequest $excuse): JsonResponse
    {
        $request->validate([
            'status'       => 'required|in:APPROVED,REJECTED',
            'review_notes' => 'nullable|string|max:500',
        ]);

        $excuse->update([
            'status'       => $request->status,
            'reviewed_by'  => Auth::id() ?? 1,
            'review_notes' => $request->review_notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->status === 'APPROVED' ? 'تم اعتماد العذر الطبي/الرسمي بنجاح.' : 'تم رفض طلب العذر.',
            'excuse'  => $excuse->load(['student.branch', 'submitter:id,name', 'reviewer:id,name']),
        ]);
    }


    // ===================================================================
    // ATTENDANCE — الحضور والغياب
    // ===================================================================

    public function getAttendance(Student $student): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->cannot('view', $student)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: لا يمكنك الاطلاع على حضور طالب يتبع فرعاً تعليمياً آخر.',
            ], 403);
        }

        $records = StudentAttendance::where('student_id', $student->id)
            ->with('recorder:id,name')
            ->latest('record_date')
            ->limit(100)
            ->get();

        $summary = StudentAttendance::where('student_id', $student->id)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json(['success' => true, 'records' => $records, 'summary' => $summary]);
    }

    public function addAttendance(Request $request, Student $student): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->cannot('update', $student)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: لا يمكنك رصد حضور لطالب يتبع فرعاً تعليمياً آخر.',
            ], 403);
        }

        $request->validate([
            'record_date'    => 'required|date',
            'status'         => 'required|in:PRESENT,ABSENT,LATE,EXCUSED',
            'absence_reason' => 'nullable|string',
        ]);

        $attendance = StudentAttendance::updateOrCreate(
            ['student_id' => $student->id, 'record_date' => $request->record_date],
            [
                'status'         => $request->status,
                'absence_reason' => $request->absence_reason,
                'recorded_by'    => Auth::id(),
            ]
        );

        return response()->json([
            'success'    => true,
            'message'    => 'تم تسجيل الحضور بنجاح.',
            'attendance' => $attendance,
        ]);
    }

    // ===================================================================
    // DOCUMENTS — المستندات
    // ===================================================================

    public function uploadDocument(Request $request, Student $student): JsonResponse
    {
        $allowedTypes = array_keys(StudentDocument::DOCUMENT_TYPES);

        $request->validate([
            'document_type'   => 'required|string|in:' . implode(',', $allowedTypes),
            'document'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'document_base64' => 'nullable|string',
            'notes'           => 'nullable|string|max:500',
        ]);

        if (!$request->hasFile('document') && empty($request->document_base64)) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى اختيار ملف بصيغة (PDF / JPG / PNG) أو التقاط صورة بالكاميرا.',
            ], 422);
        }

        $user = Auth::user();
        if ($user && $user->cannot('create', [StudentDocument::class, $student])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: لا يمكنك رفع مستندات لطالب يتبع فرعاً تعليمياً آخر.',
            ], 403);
        }

        $isReplacement = false;
        $existing = StudentDocument::where('student_id', $student->id)
            ->where('document_type', $request->document_type)
            ->where('document_type', '!=', 'OTHER')
            ->first();

        if ($existing) {
            $isReplacement = true;
            if (str_starts_with($existing->file_path, 'secure_vault/')) {
                Storage::disk('local')->delete($existing->file_path);
            } else {
                Storage::disk('public')->delete($existing->file_path);
            }
            $existing->delete();
        }

        $vault = app(\App\Services\SecureFileVaultService::class);
        $fileInput = $request->hasFile('document') ? $request->file('document') : $request->document_base64;
        $originalName = $request->hasFile('document') ? $request->file('document')->getClientOriginalName() : 'doc_' . time() . '.png';

        $vaultResult = $vault->storeSecure($fileInput, 'students/documents', $student->branch_id);
        if (!$vaultResult['success']) {
            return response()->json(['success' => false, 'message' => $vaultResult['error']], 422);
        }

        $path = $vaultResult['path'];
        $mimeType = $vaultResult['mime'];
        $hash = $vaultResult['hash'];
        $fileSize = $request->hasFile('document') 
            ? $request->file('document')->getSize() 
            : (int)(strlen($request->document_base64) * 0.75);

        $isRequired = in_array($request->document_type, [
            'NATIONAL_ID_CARD',
            'BIRTH_CERT',
            'BASIC_EDUCATION_CERT',
            'HEALTH_CERT',
        ]) || ($request->document_type === 'DISABILITY_MEDICAL_REP' && $student->has_disability);

        $doc = StudentDocument::create([
            'student_id'    => $student->id,
            'document_type' => $request->document_type,
            'original_name' => $originalName,
            'file_size'     => $fileSize,
            'mime_type'     => $mimeType,
            'is_required'   => $isRequired,
            'file_path'     => $path,
            'file_hash'     => $hash,
            'notes'         => $request->notes,
            'uploaded_by'   => Auth::id() ?? 1,
        ]);

        // إذا كان المستند صورة شخصية أو تقرير طبي خاص بالإعاقة، قم بتحديث ملف الطالب أيضاً
        if ($request->document_type === 'PERSONAL_PHOTO') {
            $student->update(['profile_photo_path' => $path]);
        } elseif ($request->document_type === 'DISABILITY_MEDICAL_REP') {
            $student->update(['medical_report_path' => $path]);
        }

        // تسجيل في السجل الموحد للأحداث
        $typeLabel = $doc->type_label;
        $eventMsg = $isReplacement ? "تم استبدال مستند: {$typeLabel}" : "تم رفع وإرفاق مستند: {$typeLabel}";
        $this->logEvent(
            $student,
            $isReplacement ? 'DOCUMENT_REPLACE' : 'DOCUMENT_UPLOAD',
            null,
            null,
            $eventMsg,
            $path,
            [
                'document_id'   => $doc->id,
                'document_type' => $request->document_type,
                'original_name' => $originalName,
                'file_size'     => $fileSize,
                'mime_type'     => $mimeType,
            ]
        );

        return response()->json([
            'success'  => true,
            'message'  => ($isReplacement ? 'تم استبدال وتحديث المستند بنجاح.' : 'تم رفع وإرفاق المستند بملف الطالب بنجاح.'),
            'document' => $doc,
            'url'      => $doc->file_url,
        ]);
    }

    public function deleteDocument(Request $request, Student $student, StudentDocument $document): JsonResponse
    {
        if ($document->student_id !== $student->id) {
            return response()->json(['success' => false, 'message' => 'هذا المستند لا ينتمي لهذا الطالب.'], 403);
        }

        $user = Auth::user();
        if ($user && $user->cannot('delete', $document)) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: لا يمكنك حذف مستندات لطالب يتبع فرعاً تعليمياً آخر.',
            ], 403);
        }

        $typeLabel = $document->type_label;
        $filePath = $document->file_path;

        if (str_starts_with((string)$filePath, 'secure_vault/')) {
            Storage::disk('local')->delete($filePath);
        } else {
            Storage::disk('public')->delete($filePath);
        }
        $document->delete();

        $this->logEvent(
            $student,
            'DOCUMENT_DELETE',
            null,
            null,
            "تم حذف المستند: {$typeLabel}",
            null,
            ['document_type' => $document->document_type]
        );

        return response()->json(['success' => true, 'message' => 'تم حذف المستند وتوثيق العملية بالسجل.']);
    }

    // ===================================================================
    // STATUS CHANGE — تغيير الحالة مباشرة
    // ===================================================================

    public function changeStatus(Request $request, Student $student, StudentStateMachineService $stateMachine): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'new_status' => 'required|in:NEW_DRAFT,PENDING_HQ,ENROLLED_ACTIVE,SUSPENDED,TRANSFERRED,GRADUATED,EXPELLED',
            'reason'     => 'required|string|min:5',
            'document'   => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $docPath = null;
        if ($request->hasFile('document')) {
            $vaultResult = app(SecureFileVaultService::class)->storeSecure(
                $request->file('document'),
                "students/{$student->id}/status_transitions",
                $student->branch_id
            );
            $docPath = $vaultResult['path'] ?? null;
        }

        try {
            $student = $stateMachine->transition(
                student: $student,
                targetStatus: $request->new_status,
                reason: $request->reason,
                documentPath: $docPath,
                user: $user
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تغيير حالة الطالب بنجاح وتوثيقه في السجل.',
                'student' => $student->only(['id', 'academic_status', 'status_label']),
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    // ===================================================================
    // STUDY TYPE CHANGE — تغيير صفة القيد
    // ===================================================================

    public function changeStudyType(Request $request, Student $student): JsonResponse
    {
        $request->validate([
            'new_type'   => 'required|in:REGULAR,INTISAB',
            'reason'     => 'required|string|min:5',
            'ref_number' => 'nullable|string',
        ]);

        if ($student->study_type === $request->new_type) {
            return response()->json(['success' => false, 'message' => 'الطالب مسجل بهذه الصفة بالفعل.'], 422);
        }

        $oldType = $student->study_type;

        DB::transaction(function () use ($student, $request, $oldType) {
            $student->update(['study_type' => $request->new_type]);
            $this->logEvent($student, 'SYSTEM_CHANGE', $oldType, $request->new_type, $request->reason, null, [
                'ref_number' => $request->ref_number,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير صفة القيد بنجاح.',
            'student' => $student->fresh()->only(['id', 'study_type', 'study_type_label']),
        ]);
    }

    // ===================================================================
    // ENROLLMENT STATUS REQUEST — طلب إيقاف/تجديد (Option B)
    // ===================================================================

    public function submitStatusRequest(Request $request, Student $student, StudentStateMachineService $stateMachine): JsonResponse
    {
        $request->validate([
            'request_type'           => 'required|in:PAUSE,RENEWAL',
            'target_academic_year_id'=> 'nullable|exists:academic_years,id',
            'reason'                 => 'required|string|min:10',
            'document'               => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = Auth::user();
        $targetStatus = ($request->request_type === 'PAUSE') ? 'SUSPENDED' : 'ENROLLED_ACTIVE';

        try {
            $statusRequest = $stateMachine->submitStatusChangeRequest(
                student: $student,
                requestedStatus: $targetStatus,
                reason: $request->reason,
                documentFile: $request->file('document'),
                requester: $user
            );

            if ($request->filled('target_academic_year_id')) {
                $statusRequest->update(['target_academic_year_id' => $request->target_academic_year_id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم رفع طلب ' . ($request->request_type === 'PAUSE' ? 'الإيقاف' : 'التجديد') . ' بنجاح وهو قيد المراجعة المركزية.',
                'request' => $statusRequest,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getStatusRequests(Student $student): JsonResponse
    {
        $requests = EnrollmentStatusRequest::where('student_id', $student->id)
            ->with(['creator:id,name', 'targetYear:id,name'])
            ->latest()
            ->get();
        return response()->json(['success' => true, 'requests' => $requests]);
    }

    // ===================================================================
    // APPROVE / REVOKE DATA — الاعتماد والتوثيق
    // ===================================================================

    public function approveData(Student $student): JsonResponse
    {
        // MED-8: Only HQ users or those with APPROVE_STUDENT_DATA permission can approve
        $user = Auth::user();
        if (!$user || (!$user->hasGlobalAccessScope() && !$user->hasPermission('APPROVE_STUDENT_DATA'))) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: اعتماد بيانات الطالب محصور في مسؤولي الإدارة العامة أو من يملك صلاحية خاصة.',
            ], 403);
        }

        if ($student->is_data_approved) {
            return response()->json(['success' => false, 'message' => 'البيانات معتمدة مسبقاً.'], 422);
        }

        $student->update([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        SystemAuditTrail::log(
            eventType: 'STUDENT_DATA_APPROVED',
            modelType: Student::class,
            modelId: $student->id,
            description: "اعتماد وتوثيق بيانات الطالب: {$student->full_name} ({$student->academic_number}).",
            branchId: $student->branch_id,
            newValues: ['approved_by' => $user->id, 'approved_at' => now()->toIso8601String()],
            severity: 'CRITICAL'
        );

        return response()->json(['success' => true, 'message' => 'تم اعتماد بيانات الطالب بنجاح.']);
    }

    public function revokeData(Student $student): JsonResponse
    {
        // MED-8: Only HQ users or those with APPROVE_STUDENT_DATA permission can revoke
        $user = Auth::user();
        if (!$user || (!$user->hasGlobalAccessScope() && !$user->hasPermission('APPROVE_STUDENT_DATA'))) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: فك الاعتماد محصور في مسؤولي الإدارة العامة أو من يملك صلاحية خاصة.',
            ], 403);
        }

        $student->update(['approved_by' => null, 'approved_at' => null]);

        SystemAuditTrail::log(
            eventType: 'STUDENT_DATA_APPROVAL_REVOKED',
            modelType: Student::class,
            modelId: $student->id,
            description: "إلغاء وفك اعتماد بيانات الطالب: {$student->full_name} ({$student->academic_number}).",
            branchId: $student->branch_id,
            severity: 'WARNING'
        );

        return response()->json(['success' => true, 'message' => 'تم فك اعتماد البيانات.']);
    }

    public function verifyData(Student $student): JsonResponse
    {
        // التحقق من وجود المستندات الإلزامية
        $hasEducationDoc = StudentDocument::where('student_id', $student->id)
            ->where('document_type', 'BASIC_EDUCATION_CERT')
            ->exists();

        if (!$hasEducationDoc) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن توثيق الطالب — يجب رفع شهادة التعليم الأساسي أولاً.',
            ], 422);
        }

        $student->update([
            'data_verified_by' => Auth::id(),
            'data_verified_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'تم توثيق بيانات الطالب بنجاح.']);
    }

    // ===================================================================
    // TRANSFER — نقل الطالب
    // ===================================================================

    public function transferStudent(Request $request, Student $student): JsonResponse
    {
        $request->validate([
            'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
            'reason'       => 'required|string|min:5',
        ]);

        if ($student->branch_id == $request->to_branch_id) {
            return response()->json(['success' => false, 'message' => 'الطالب مسجل في هذا الفرع بالفعل.'], 422);
        }

        $docPath   = null;
        $oldBranch = $student->branch_id;

        if ($request->hasFile('document')) {
            $docPath = $request->file('document')->store("students/{$student->id}/transfers", 'public');
        }

        DB::transaction(function () use ($student, $request, $oldBranch, $docPath) {
            // تسجيل سجل النقل
            \App\Models\StudentTransfer::create([
                'student_id'    => $student->id,
                'from_branch_id'=> $oldBranch,
                'to_branch_id'  => $request->to_branch_id,
                'reason'        => $request->reason,
                'status'        => 'APPROVED',
                'requested_by'  => Auth::id(),
                'approved_by'   => Auth::id(),
                'approved_at'   => now(),
            ]);

            // تحديث فرع الطالب
            $student->update(['branch_id' => $request->to_branch_id]);

            // تسجيل في السجل الموحد
            $this->logEvent($student, 'TRANSFER', (string)$oldBranch, (string)$request->to_branch_id, $request->reason, $docPath, [
                'from_branch_id' => $oldBranch,
                'to_branch_id'   => $request->to_branch_id,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'تم نقل الطالب بنجاح وتوثيق العملية في السجل.',
            'student' => $student->fresh(['branch'])->only(['id', 'branch_id', 'branch']),
        ]);
    }

    // ===================================================================
    // ACADEMIC PLACEMENT — تحديث التنسيب الأكاديمي (المرحلة والقسم)
    // ===================================================================

    public function changeAcademicPlacement(Request $request, Student $student): JsonResponse
    {
        $request->validate([
            'current_study_year_id' => 'required|exists:study_years,id',
            'department_id'         => 'required|exists:departments,id',
            'reason'                => 'required|string|min:3',
        ], [
            'current_study_year_id.required' => 'المرحلة الدراسية مطلوبة.',
            'department_id.required'         => 'القسم التخصصي مطلوب.',
            'reason.required'                => 'سبب التعديل والتنسيب مطلوب.',
        ]);

        $oldStudyYear = $student->currentStudyYear?->name ?? 'غير محدد';
        $oldDept = $student->department?->name ?? 'غير محدد';

        $oldSyId = $student->current_study_year_id;
        $oldDepId = $student->department_id;

        $student->update([
            'current_study_year_id' => $request->current_study_year_id,
            'department_id'         => $request->department_id,
        ]);

        $newStudent = $student->fresh(['currentStudyYear', 'department', 'branch']);
        $newStudyYear = $newStudent->currentStudyYear?->name ?? 'غير محدد';
        $newDept = $newStudent->department?->name ?? 'غير محدد';

        // تسجيل في السجل الموحد
        $this->logEvent(
            $student,
            'ACADEMIC_PLACEMENT_CHANGE',
            "{$oldStudyYear} / {$oldDept}",
            "{$newStudyYear} / {$newDept}",
            $request->reason,
            null,
            [
                'old_study_year_id' => $oldSyId,
                'new_study_year_id' => $request->current_study_year_id,
                'old_department_id' => $oldDepId,
                'new_department_id' => $request->department_id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التنسيب الأكاديمي (المرحلة والقسم) بنجاح وتوثيقه في سجل الطالب.',
            'student' => $newStudent,
        ]);
    }

    // ===================================================================
    // SMS — إرسال بيانات ولي الأمر (Placeholder)
    // ===================================================================

    public function sendGuardianCredentials(Student $student): JsonResponse
    {
        if (empty($student->guardian_phone)) {
            return response()->json(['success' => false, 'message' => 'رقم هاتف ولي الأمر غير مسجل بملف الطالب.'], 422);
        }

        // توليد كلمة مرور عشوائية آمنة
        $newPassword = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        // TODO: ربط مزود SMS هنا
        // SmsService::send($student->guardian_phone, "بيانات دخول الطالب {$student->full_name} ...");

        return response()->json([
            'success' => true,
            'message' => "تم إرسال رسالة نصية إلى ولي الأمر على الرقم ({$student->guardian_phone}) — [وضع اختبار: خدمة SMS قيد الربط].",
        ]);
    }

    // ===================================================================
    // Private Helper — تسجيل الأحداث في السجل الموحد
    // ===================================================================

    private function logEvent(
        Student $student,
        string $eventType,
        ?string $oldValue,
        ?string $newValue,
        ?string $reason,
        ?string $docPath,
        array $meta = []
    ): void {
        StudentStatusHistory::create([
            'student_id'    => $student->id,
            'event_type'    => $eventType,
            'old_status'    => $oldValue,
            'new_status'    => $newValue,
            'reason'        => $reason,
            'document_path' => $docPath,
            'meta'          => $meta ?: null,
            'changed_by'    => Auth::id(),
            'event_date'    => now(),
        ]);
    }
}
