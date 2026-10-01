<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Student;
use App\Models\EnrollmentStatusRequest;
use App\Models\StudyTypeChangeRequest;
use App\Models\StudentTransfer;
use App\Models\RequestDiscussion;
use App\Models\Branch;
use App\Models\AcademicYear;
use App\Models\SystemAuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentWorkflowController extends Controller
{
    /**
     * Get live summary KPI counters for all 3 workflow tracks.
     */
    public function getSummaryCounters(): JsonResponse
    {
        $user = Auth::user();
        $isRestricted = $user && !$user->hasGlobalAccessScope() && !empty($user->branch_id);
        $userBranchId = $isRestricted ? (int)$user->branch_id : null;

        $statusQuery = EnrollmentStatusRequest::where('final_status', 'PENDING');
        $systemQuery = StudyTypeChangeRequest::where('final_status', 'PENDING');
        $transferQuery = StudentTransfer::where('status', 'PENDING');
        $waitingCentralQuery = StudentTransfer::where('status', 'PENDING')->whereNull('central_affairs_statement');
        $waitingReceivingQuery = StudentTransfer::where('status', 'PENDING')->whereNotNull('central_affairs_statement')->where('receiving_branch_status', 'PENDING');

        if ($isRestricted) {
            $statusQuery->whereHas('student', fn($q) => $q->where('branch_id', $userBranchId));
            $systemQuery->whereHas('student', fn($q) => $q->where('branch_id', $userBranchId));
            $transferQuery->where(fn($q) => $q->where('from_branch_id', $userBranchId)->orWhere('to_branch_id', $userBranchId));
            $waitingCentralQuery->where('from_branch_id', $userBranchId);
            $waitingReceivingQuery->where('to_branch_id', $userBranchId);
        }

        $statusPending = $statusQuery->count();
        $systemPending = $systemQuery->count();
        $transferPending = $transferQuery->count();
        $waitingCentral = $waitingCentralQuery->count();
        $waitingReceiving = $waitingReceivingQuery->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'status_pending_count' => $statusPending,
                'system_pending_count' => $systemPending,
                'transfer_pending_count' => $transferPending,
                'total_pending_workflow' => $statusPending + $systemPending + $transferPending,
                'transfer_kpis' => [
                    'waiting_central_affairs' => $waitingCentral,
                    'waiting_receiving_branch' => $waitingReceiving,
                ]
            ]
        ]);
    }

    /**
     * Fetch requests roster with filtering, sorting, student age & absence metrics, and pagination.
     */
    public function getRequests(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isRestricted = $user && !$user->hasGlobalAccessScope() && !empty($user->branch_id);

        $track = $request->input('track', 'status'); // status | system | transfer
        $scope = $request->input('scope', 'pending'); // pending | completed
        $branchId = $isRestricted ? (int)$user->branch_id : $request->input('branch_id', 'all');
        $sortBy = $request->input('sort_by', 'newest'); // newest | oldest | youngest | most_absent | alphabetical
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 25);

        $results = [];

        if ($track === 'status') {
            $query = EnrollmentStatusRequest::with(['student.branch', 'student.studyYear', 'targetYear']);

            if ($scope === 'pending') {
                $query->where('final_status', 'PENDING');
            } else {
                $query->whereIn('final_status', ['APPROVED', 'REJECTED']);
            }

            if ($branchId !== 'all' && !empty($branchId)) {
                $query->whereHas('student', fn($q) => $q->where('branch_id', (int)$branchId));
            }

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('reason', 'like', "%{$search}%")
                      ->orWhereHas('student', function($sq) use ($search) {
                          $sq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('family_name', 'like', "%{$search}%")
                            ->orWhere('academic_number', 'like', "%{$search}%");
                      });
                });
            }

            $rawItems = $query->get();

            // Transform with student age, attendance, behaviors and 2-stage governance
            $transformed = $rawItems->map(function($item) {
                $student = $item->student;
                $age = $student && $student->birth_date ? Carbon::parse($student->birth_date)->age : null;
                $absentCount = DB::table('student_attendance')->where('student_id', $item->student_id)->where('status', 'ABSENT')->count();
                $behaviorsCount = DB::table('student_behaviors')->where('student_id', $item->student_id)->count();
                $discussionsCount = RequestDiscussion::where('request_type', 'STATUS')->where('request_id', $item->id)->count();

                // Check submission frequency
                $repeatCount = EnrollmentStatusRequest::where('student_id', $item->student_id)->count();

                $branchName = $student?->branch?->name ?? 'الفرع';
                $branchManagerInfo = 'مدير ' . $branchName;
                $hqOfficialTitle = 'رئيس قسم الدراسة والامتحانات (الإدارة المركزية)';

                return [
                    'id' => $item->id,
                    'request_type' => $item->request_type,
                    'type_label' => $item->request_type === 'PAUSE' ? 'إيقاف قيد' : 'تجديد قيد',
                    'reason' => $item->reason,
                    'document_path' => $item->document_path,
                    'branch_status' => $item->branch_status,
                    'hq_status' => $item->hq_status,
                    'final_status' => $item->final_status,
                    'rejection_notes' => $item->rejection_notes,
                    'submitted_by_title' => $branchManagerInfo,
                    'hq_approver_title' => $hqOfficialTitle,
                    'workflow_stage_label' => $item->final_status === 'APPROVED'
                        ? 'معتمد نهائياً من رئيس قسم الدراسة والامتحانات'
                        : ($item->final_status === 'REJECTED'
                            ? 'مرفوض من رئيس قسم الدراسة والامتحانات'
                            : 'مقدم من مدير الفرع ➔ بانتظار موافقة رئيس قسم الدراسة والامتحانات'),
                    'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i') : '',
                    'created_at_human' => $item->created_at ? $item->created_at->diffForHumans() : '',
                    'student' => $student ? [
                        'id' => $student->id,
                        'full_name' => $student->first_name . ' ' . $student->family_name,
                        'academic_number' => $student->academic_number,
                        'branch_name' => $student->branch?->name ?? 'غير محدد',
                        'study_year_name' => $student->studyYear?->name ?? 'غير محدد',
                        'age' => $age,
                        'absent_count' => $absentCount,
                        'behaviors_count' => $behaviorsCount,
                    ] : null,
                    'repeat_count' => $repeatCount,
                    'discussions_count' => $discussionsCount,
                ];
            });

            $results = $this->sortAndPaginate($transformed, $sortBy, $perPage, $request->input('page', 1));

        } elseif ($track === 'system') {
            $query = StudyTypeChangeRequest::with(['student.branch', 'student.studyYear']);

            if ($scope === 'pending') {
                $query->where('final_status', 'PENDING');
            } else {
                $query->whereIn('final_status', ['APPROVED', 'REJECTED']);
            }

            if ($branchId !== 'all' && !empty($branchId)) {
                $query->whereHas('student', fn($q) => $q->where('branch_id', (int)$branchId));
            }

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('reason', 'like', "%{$search}%")
                      ->orWhereHas('student', function($sq) use ($search) {
                          $sq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('family_name', 'like', "%{$search}%")
                            ->orWhere('academic_number', 'like', "%{$search}%");
                      });
                });
            }

            $rawItems = $query->get();

            $transformed = $rawItems->map(function($item) {
                $student = $item->student;
                $age = $student && $student->birth_date ? Carbon::parse($student->birth_date)->age : null;
                $absentCount = DB::table('student_attendance')->where('student_id', $item->student_id)->where('status', 'ABSENT')->count();
                $behaviorsCount = DB::table('student_behaviors')->where('student_id', $item->student_id)->count();
                $discussionsCount = RequestDiscussion::where('request_type', 'SYSTEM')->where('request_id', $item->id)->count();

                $branchName = $student?->branch?->name ?? 'الفرع';
                $branchManagerInfo = 'مدير ' . $branchName;
                $hqOfficialTitle = 'رئيس قسم الدراسة والامتحانات (الإدارة المركزية)';

                return [
                    'id' => $item->id,
                    'old_type' => $item->old_type,
                    'old_type_label' => $item->old_type === 'REGULAR' ? 'نظامي' : 'انتساب',
                    'new_type' => $item->new_type,
                    'new_type_label' => $item->new_type === 'REGULAR' ? 'نظامي' : 'انتساب',
                    'reason' => $item->reason,
                    'is_full_absence' => (bool)$item->is_full_absence,
                    'document_path' => $item->document_path,
                    'branch_status' => $item->branch_status,
                    'hq_status' => $item->hq_status,
                    'final_status' => $item->final_status,
                    'submitted_by_title' => $branchManagerInfo,
                    'hq_approver_title' => $hqOfficialTitle,
                    'workflow_stage_label' => $item->final_status === 'APPROVED'
                        ? 'معتمد نهائياً من رئيس قسم الدراسة والامتحانات'
                        : ($item->final_status === 'REJECTED'
                            ? 'مرفوض من رئيس قسم الدراسة والامتحانات'
                            : 'مقدم من مدير الفرع ➔ بانتظار موافقة رئيس قسم الدراسة والامتحانات'),
                    'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i') : '',
                    'created_at_human' => $item->created_at ? $item->created_at->diffForHumans() : '',
                    'student' => $student ? [
                        'id' => $student->id,
                        'full_name' => $student->first_name . ' ' . $student->family_name,
                        'academic_number' => $student->academic_number,
                        'branch_name' => $student->branch?->name ?? 'غير محدد',
                        'study_year_name' => $student->studyYear?->name ?? 'غير محدد',
                        'age' => $age,
                        'absent_count' => $absentCount,
                        'behaviors_count' => $behaviorsCount,
                    ] : null,
                    'discussions_count' => $discussionsCount,
                ];
            });

            $results = $this->sortAndPaginate($transformed, $sortBy, $perPage, $request->input('page', 1));

        } elseif ($track === 'transfer') {
            $query = StudentTransfer::with(['student.studyYear', 'fromBranch', 'toBranch']);

            if ($scope === 'pending') {
                $query->where('status', 'PENDING');
            } else {
                $query->whereIn('status', ['APPROVED', 'REJECTED']);
            }

            if ($branchId !== 'all' && !empty($branchId)) {
                $query->where(function($q) use ($branchId) {
                    $q->where('from_branch_id', (int)$branchId)
                      ->orWhere('to_branch_id', (int)$branchId);
                });
            }

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('reason', 'like', "%{$search}%")
                      ->orWhereHas('student', function($sq) use ($search) {
                          $sq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('family_name', 'like', "%{$search}%")
                            ->orWhere('academic_number', 'like', "%{$search}%");
                      });
                });
            }

            $rawItems = $query->get();

            $transformed = $rawItems->map(function($item) {
                $student = $item->student;
                $age = $student && $student->birth_date ? Carbon::parse($student->birth_date)->age : null;
                $absentCount = DB::table('student_attendance')->where('student_id', $item->student_id)->where('status', 'ABSENT')->count();
                $discussionsCount = RequestDiscussion::where('request_type', 'TRANSFER')->where('request_id', $item->id)->count();

                // Determine step bottleneck
                $stepStage = 'CENTRAL_MEMO'; // Stage 1
                if ($item->central_affairs_statement) {
                    $stepStage = 'RECEIVING_BRANCH'; // Stage 2
                }

                $fromBranchName = $item->fromBranch?->name ?? 'الفرع المصدر';
                $branchManagerInfo = 'مدير ' . $fromBranchName;
                $hqOfficialTitle = 'رئيس قسم الدراسة والامتحانات (الإدارة المركزية)';

                return [
                    'id' => $item->id,
                    'from_branch_id' => $item->from_branch_id,
                    'from_branch_name' => $fromBranchName,
                    'to_branch_id' => $item->to_branch_id,
                    'to_branch_name' => $item->toBranch?->name ?? 'الفرع المستقبل',
                    'reason' => $item->reason,
                    'status' => $item->status,
                    'central_affairs_statement' => $item->central_affairs_statement,
                    'central_affairs_approved_at' => $item->central_affairs_approved_at ? Carbon::parse($item->central_affairs_approved_at)->format('Y-m-d H:i') : null,
                    'receiving_branch_status' => $item->receiving_branch_status,
                    'receiving_branch_decision_notes' => $item->receiving_branch_decision_notes,
                    'step_stage' => $stepStage,
                    'submitted_by_title' => $branchManagerInfo,
                    'hq_approver_title' => $hqOfficialTitle,
                    'workflow_stage_label' => $item->status === 'APPROVED'
                        ? 'معتمد نهائياً ومنفذ آلياً'
                        : ($item->status === 'REJECTED'
                            ? 'طلب نقل مرفوض'
                            : ($stepStage === 'CENTRAL_MEMO' ? 'مقدم من مدير الفرع ➔ بانتظار إفادة الشؤون المركزية ورئيس قسم الدراسة والامتحانات' : 'بانتظار قرار الضم من الفرع المستقبل')),
                    'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i') : '',
                    'created_at_human' => $item->created_at ? $item->created_at->diffForHumans() : '',
                    'student' => $student ? [
                        'id' => $student->id,
                        'full_name' => $student->first_name . ' ' . $student->family_name,
                        'academic_number' => $student->academic_number,
                        'study_year_name' => $student->studyYear?->name ?? 'غير محدد',
                        'age' => $age,
                        'absent_count' => $absentCount,
                    ] : null,
                    'discussions_count' => $discussionsCount,
                ];
            });

            $results = $this->sortAndPaginate($transformed, $sortBy, $perPage, $request->input('page', 1));
        }

        return response()->json([
            'status' => 'success',
            'data' => $results,
        ]);
    }

    /**
     * Sort and paginate in memory collection.
     */
    protected function sortAndPaginate($collection, string $sortBy, $perPage, int $currentPage): array
    {
        switch ($sortBy) {
            case 'oldest':
                $sorted = $collection->sortBy('id')->values();
                break;
            case 'youngest':
                $sorted = $collection->sortBy(fn($item) => $item['student']['age'] ?? 999)->values();
                break;
            case 'most_absent':
                $sorted = $collection->sortByDesc(fn($item) => $item['student']['absent_count'] ?? 0)->values();
                break;
            case 'alphabetical':
                $sorted = $collection->sortBy(fn($item) => $item['student']['full_name'] ?? '')->values();
                break;
            case 'newest':
            default:
                $sorted = $collection->sortByDesc('id')->values();
                break;
        }

        $total = $sorted->count();
        $perPageInt = ($perPage === 'all' || (int)$perPage <= 0) ? ($total > 0 ? $total : 1) : (int)$perPage;
        $offset = ($currentPage - 1) * $perPageInt;
        $items = $sorted->slice($offset, $perPageInt)->values();

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPageInt,
            'current_page' => $currentPage,
            'last_page' => (int)ceil($total / $perPageInt) ?: 1,
        ];
    }

    /**
     * Handle Action on Pause / Renewal Request (APPROVE, REJECT, RETURN).
     */
    public function handleStatusAction(Request $request, int $id): JsonResponse
    {
        $action = $request->input('action'); // APPROVE | REJECT | RETURN
        $notes = $request->input('decision_notes', '');

        $req = EnrollmentStatusRequest::findOrFail($id);
        $user = Auth::user();

        // Four-Eyes Principle (1.8): Submitter cannot approve
        if ($user && $req->created_by && (int)$req->created_by === (int)$user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'انتهاك مبدأ الرقابة الثنائية (Four-Eyes Principle): لا يمكن لمقدم طلب تعديل القيد اعتماده أو البت فيه.',
            ], 403);
        }

        // HQ approval restriction (Option B)
        if ($user && !$user->hasGlobalAccessScope() && !$user->hasPermission('APPROVE_STUDENT_STATUS')) {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح: اعتماد طلبات تعديل قيد الطلاب محصور في الإدارة المركزية (شؤون الطلاب العامة).',
            ], 403);
        }

        DB::transaction(function() use ($req, $action, $notes, $user, $request) {
            $now = Carbon::now();

            if ($action === 'APPROVE') {
                $req->update([
                    'branch_status' => 'APPROVED',
                    'hq_status' => 'APPROVED',
                    'final_status' => 'APPROVED',
                    'rejection_notes' => null,
                    'hq_reviewed_by' => $user?->id,
                ]);

                // Update student academic_status
                $student = Student::lockForUpdate()->find($req->student_id);
                if ($student) {
                    $oldStatus = $student->academic_status;
                    $newStatus = ($req->request_type === 'PAUSE') ? 'PAUSED' : 'ENROLLED_ACTIVE';
                    $student->update(['academic_status' => $newStatus]);

                    // Log in history
                    DB::table('student_status_history')->insert([
                        'student_id' => $student->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'event_type' => $req->request_type === 'PAUSE' ? 'PAUSE' : 'RENEWAL',
                        'reason' => $req->reason . ' - ' . $notes,
                        'changed_by' => $user?->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            } elseif ($action === 'REJECT') {
                $req->update([
                    'final_status' => 'REJECTED',
                    'hq_status' => 'REJECTED',
                    'rejection_notes' => $notes ?: 'تم رفض الطلب لعدم استيفاء الشروط واللوائح الأكاديمية.',
                    'hq_reviewed_by' => $user?->id,
                ]);
            } else { // RETURN
                $req->update([
                    'branch_status' => 'PENDING',
                    'hq_status' => 'PENDING',
                    'final_status' => 'PENDING',
                    'rejection_notes' => 'إعادة الطلب للفرع: ' . $notes,
                ]);
            }

            SystemAuditTrail::log(
                eventType: 'STUDENT_ENROLLMENT_STATUS_DECIDED',
                modelType: EnrollmentStatusRequest::class,
                modelId: $req->id,
                description: "تم اتخاذ قرار ({$action}) بشأن طلب إيقاف/تجديد قيد الطالب رقم #{$req->student_id}.",
                branchId: $user?->branch_id,
                newValues: ['action' => $action, 'final_status' => $req->final_status, 'decided_by' => $user?->id],
                severity: 'WARNING',
                request: $request
            );
        });

        return response()->json([
            'status' => 'success',
            'message' => 'تم اتخاذ القرار الإداري وتحديث حالة الطالب وسجل القيد بنجاح.',
        ]);
    }

    /**
     * Handle Action on Study Type Change Request (REGULAR <-> INTISAB).
     */
    public function handleSystemAction(Request $request, int $id): JsonResponse
    {
        $action = $request->input('action'); // APPROVE | REJECT
        $notes = $request->input('decision_notes', '');

        $req = StudyTypeChangeRequest::findOrFail($id);
        $user = Auth::user();

        // Four-Eyes Principle (1.8): Submitter cannot approve
        if ($user && $req->created_by && (int)$req->created_by === (int)$user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'انتهاك مبدأ الرقابة الثنائية: لا يمكن لمقدم طلب تغيير صفة القيد اعتماده.',
            ], 403);
        }

        // HQ approval restriction
        if ($user && !$user->hasGlobalAccessScope() && !$user->hasPermission('APPROVE_STUDY_TYPE')) {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح: اعتماد تغيير صفة القيد محصور في إدارة شؤون الطلاب المركزية.',
            ], 403);
        }

        DB::transaction(function() use ($req, $action, $notes, $user, $request) {
            $now = Carbon::now();

            if ($action === 'APPROVE') {
                $req->update([
                    'branch_status' => 'APPROVED',
                    'hq_status' => 'APPROVED',
                    'final_status' => 'APPROVED',
                ]);

                $student = Student::lockForUpdate()->find($req->student_id);
                if ($student) {
                    $oldType = $student->study_type;
                    $newType = $req->new_type;
                    $student->update(['study_type' => $newType]);

                    // Log in status history
                    DB::table('student_status_history')->insert([
                        'student_id' => $student->id,
                        'old_status' => $oldType,
                        'new_status' => $newType,
                        'event_type' => 'SYSTEM_CHANGE',
                        'reason' => 'تغيير صفة القيد: ' . $req->reason . ' - ' . $notes,
                        'changed_by' => $user?->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            } else {
                $req->update([
                    'final_status' => 'REJECTED',
                    'hq_status' => 'REJECTED',
                ]);
            }

            SystemAuditTrail::log(
                eventType: 'STUDY_TYPE_CHANGE_DECIDED',
                modelType: StudyTypeChangeRequest::class,
                modelId: $req->id,
                description: "تم البت في طلب تغيير صفة القيد ({$action}) للطالب رقم #{$req->student_id}.",
                branchId: $user?->branch_id,
                newValues: ['action' => $action, 'final_status' => $req->final_status, 'decided_by' => $user?->id],
                severity: 'WARNING',
                request: $request
            );
        });

        return response()->json([
            'status' => 'success',
            'message' => 'تم اعتماد قرار تغيير صفة القيد وتحديث ملف وسجل الطالب تلقائياً.',
        ]);
    }

    /**
     * Handle Multi-step Handshake for Student Transfers.
     */
    public function handleTransferStep(Request $request, int $id): JsonResponse
    {
        $step = $request->input('step'); // CENTRAL_MEMO | RECEIVING_BRANCH
        $notes = $request->input('notes', '');
        $status = $request->input('status', 'APPROVED'); // APPROVED | REJECTED

        $transfer = StudentTransfer::findOrFail($id);
        $user = Auth::user();
        $now = Carbon::now();

        // Four-Eyes Principle (1.8): Submitter cannot approve
        if ($user && $transfer->requested_by && (int)$transfer->requested_by === (int)$user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'انتهاك مبدأ الرقابة الثنائية (Four-Eyes Principle): لا يمكن لمنشئ طلب النقل أن يعتمده بنفسه.',
            ], 403);
        }

        // Scope/Role verification
        if ($step === 'CENTRAL_MEMO' && $user && !$user->hasGlobalAccessScope()) {
            return response()->json([
                'status' => 'error',
                'message' => 'غير مصرح: إحالة النقل المركزية محصورة في إدارة شؤون الطلاب العامة.',
            ], 403);
        }

        if ($step === 'RECEIVING_BRANCH' && $user && !$user->hasGlobalAccessScope()) {
            if ((int)$user->branch_id !== (int)$transfer->to_branch_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'غير مصرح: قرار قبول النقل محصور في إدارة الفرع المستقبل فقط.',
                ], 403);
            }
        }

        DB::transaction(function() use ($transfer, $step, $notes, $status, $user, $now, $request) {
            if ($step === 'CENTRAL_MEMO') {
                $transfer->update([
                    'central_affairs_statement' => $notes ?: 'تمت دراسة الطلب وإحالته للفرع المستقبل لضم الطالب.',
                    'central_affairs_approved_by' => $user?->id,
                    'central_affairs_approved_at' => $now,
                ]);
            } elseif ($step === 'RECEIVING_BRANCH') {
                if ($status === 'APPROVED') {
                    $transfer->update([
                        'receiving_branch_status' => 'APPROVED',
                        'receiving_branch_decision_notes' => $notes ?: 'تمت الموافقة وتأكيد ضم الطالب بالفرع.',
                        'receiving_branch_decided_by' => $user?->id,
                        'receiving_branch_decided_at' => $now,
                        'status' => 'APPROVED',
                        'approved_by' => $user?->id,
                        'approved_at' => $now,
                    ]);

                    // Transfer the student's branch
                    $student = Student::lockForUpdate()->find($transfer->student_id);
                    if ($student) {
                        $oldBranch = $student->branch_id;
                        $student->update(['branch_id' => $transfer->to_branch_id]);

                        DB::table('student_status_history')->insert([
                            'student_id' => $student->id,
                            'old_status' => 'BRANCH_' . $oldBranch,
                            'new_status' => 'BRANCH_' . $transfer->to_branch_id,
                            'event_type' => 'TRANSFER',
                            'reason' => 'نقل وضم الطالب إلى فرع جديد: ' . $transfer->reason,
                            'changed_by' => $user?->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                } else {
                    $transfer->update([
                        'receiving_branch_status' => 'REJECTED',
                        'receiving_branch_decision_notes' => $notes ?: 'اعتذار الفرع المستقبل لعدم توفر السعة الاستيعابية.',
                        'receiving_branch_decided_by' => $user?->id,
                        'receiving_branch_decided_at' => $now,
                        'status' => 'REJECTED',
                    ]);
                }
            }

            SystemAuditTrail::log(
                eventType: 'STUDENT_TRANSFER_STEP_PROCESSED',
                modelType: StudentTransfer::class,
                modelId: $transfer->id,
                description: "تمت معالجة مرحلة النقل ({$step}) للطالب رقم #{$transfer->student_id} بالحالة: {$status}.",
                branchId: $user?->branch_id ?? $transfer->to_branch_id,
                newValues: ['step' => $step, 'status' => $status, 'decided_by' => $user?->id],
                severity: 'WARNING',
                request: $request
            );
        });

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ إجراء المصافحة ونقل تبعية الطالب بالفرع بنجاح.',
        ]);
    }

    /**
     * Get or post comments for request internal discussion feed.
     */
    public function getComments(string $type, int $id): JsonResponse
    {
        $comments = RequestDiscussion::where('request_type', strtoupper($type))
            ->where('request_id', $id)
            ->with('user')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'user_name' => $c->user ? $c->user->name : 'موظف مختص',
                'comment_text' => $c->comment_text,
                'created_at' => $c->created_at ? $c->created_at->format('Y-m-d H:i') : '',
                'created_at_human' => $c->created_at ? $c->created_at->diffForHumans() : '',
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $comments,
        ]);
    }

    public function addComment(Request $request, string $type, int $id): JsonResponse
    {
        $commentText = $request->input('comment_text') ?? $request->input('comment');
        if (empty($commentText) || !is_string($commentText)) {
            return response()->json(['status' => 'error', 'message' => 'نص التعليق مطلوب.'], 422);
        }
        $user = Auth::user();

        $comment = RequestDiscussion::create([
            'request_type' => strtoupper($type),
            'request_id' => $id,
            'user_id' => $user ? $user->id : 1,
            'comment_text' => trim($commentText),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تمت إضافة التعليق في صندوق المناقشات.',
            'data' => [
                'id' => $comment->id,
                'user_name' => $user ? $user->name : 'موظف مختص',
                'comment' => $comment->comment_text,
                'comment_text' => $comment->comment_text,
                'created_at' => $comment->created_at ? $comment->created_at->format('Y-m-d H:i') : '',
            ],
        ]);
    }

    /**
     * Export Transfer Follow-up CSV (tracking delayed branches).
     */
    public function exportTransferFollowup(): StreamedResponse
    {
        $user = Auth::user();
        $query = StudentTransfer::with(['student', 'fromBranch', 'toBranch']);
        if ($user && !$user->hasGlobalAccessScope() && $user->branch_id) {
            $query->where(function ($q) use ($user) {
                $q->where('from_branch_id', $user->branch_id)
                  ->orWhere('to_branch_id', $user->branch_id);
            });
        }
        $transfers = $query->orderBy('id', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="transfer_followup_' . date('Y_m_d_His') . '.csv"',
        ];

        return response()->stream(function () use ($transfers) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ت',
                'رقم الطلب',
                'اسم الطالب',
                'رقم القيد',
                'من فرع',
                'إلى فرع',
                'تاريخ التقديم',
                'حالة إفادة الشؤون المركزية',
                'قرار الفرع المستقبل',
                'الحالة النهائية',
                'الجهة المتأخرة الحالية',
            ]);

            foreach ($transfers as $idx => $t) {
                $delayedEntity = 'مكتمل';
                if ($t->status === 'PENDING') {
                    if (!$t->central_affairs_statement) {
                        $delayedEntity = 'الشؤون التعليمية المركزية';
                    } elseif ($t->receiving_branch_status === 'PENDING') {
                        $delayedEntity = 'الفرع المستقبل (' . ($t->toBranch?->name ?? '') . ')';
                    }
                }

                fputcsv($handle, [
                    $idx + 1,
                    $t->id,
                    $t->student ? ($t->student->first_name . ' ' . $t->student->family_name) : '',
                    $t->student?->academic_number ?? '',
                    $t->fromBranch?->name ?? '',
                    $t->toBranch?->name ?? '',
                    $t->created_at ? $t->created_at->format('Y-m-d') : '',
                    $t->central_affairs_statement ? 'تمت الإفادة' : 'بانتظار الإفادة',
                    $t->receiving_branch_status === 'APPROVED' ? 'موافقة' : ($t->receiving_branch_status === 'REJECTED' ? 'رفض' : 'بانتظار القرار'),
                    $t->status === 'APPROVED' ? 'معتمد' : ($t->status === 'REJECTED' ? 'مرفوض' : 'معلق'),
                    $delayedEntity,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Submit a new student request from Branch Manager to HQ (Head of Study & Exams).
     */
    public function submitBranchRequest(Request $request): JsonResponse
    {
        $request->validate([
            'track' => 'required|in:status,system,transfer',
            'student_id' => 'required|exists:students,id',
            'reason' => 'required|string|min:4',
        ], [
            'track.required' => 'نوع مسار الطلب مطلوب.',
            'student_id.required' => 'يرجى تحديد الطالب.',
            'reason.required' => 'يرجى كتابة أسباب ومبررات الطلب.',
        ]);

        $user = Auth::user();
        $student = Student::findOrFail($request->student_id);

        if ($user && !$user->hasGlobalAccessScope() && $user->branch_id && (int)$student->branch_id !== (int)$user->branch_id) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح لك بتقديم طلب لطالب من خارج فرعك.'], 403);
        }

        if ($request->track === 'status') {
            $request->validate([
                'request_type' => 'required|in:PAUSE,RENEWAL',
            ]);

            $docPath = null;
            if ($request->hasFile('document')) {
                $docPath = $request->file('document')->store("requests/status/{$student->id}", 'public');
            }

            $item = EnrollmentStatusRequest::create([
                'student_id' => $student->id,
                'request_type' => $request->request_type,
                'target_academic_year_id' => $student->enrolled_academic_year_id,
                'reason' => $request->reason,
                'document_path' => $docPath,
                'branch_status' => 'APPROVED', // تم التقديم والتأييد من مدير الفرع
                'hq_status' => 'PENDING',      // بانتظار موافقة رئيس قسم الدراسة والامتحانات
                'final_status' => 'PENDING',
                'created_by' => $user?->id,
                'branch_reviewed_by' => $user?->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم رفع وتأييد الطلب من مدير الفرع بنجاح، وهو الآن بانتظار موافقة واعتماد رئيس قسم الدراسة والامتحانات (الإدارة المركزية).',
                'data' => $item,
            ]);

        } elseif ($request->track === 'system') {
            $request->validate([
                'new_type' => 'required|in:REGULAR,INTISAB',
            ]);

            $docPath = null;
            if ($request->hasFile('document')) {
                $docPath = $request->file('document')->store("requests/system/{$student->id}", 'public');
            }

            $item = StudyTypeChangeRequest::create([
                'student_id' => $student->id,
                'old_type' => $student->study_type ?? 'REGULAR',
                'new_type' => $request->new_type,
                'reason' => $request->reason,
                'document_path' => $docPath,
                'branch_status' => 'APPROVED', // تم التقديم والتأييد من مدير الفرع
                'hq_status' => 'PENDING',      // بانتظار موافقة رئيس قسم الدراسة والامتحانات
                'final_status' => 'PENDING',
                'created_by' => $user?->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم تقديم طلب تغيير صفة القيد بتأييد مدير الفرع، وهو الآن بانتظار اعتماد رئيس قسم الدراسة والامتحانات (الإدارة المركزية).',
                'data' => $item,
            ]);

        } elseif ($request->track === 'transfer') {
            $request->validate([
                'to_branch_id' => 'required|exists:branches,id',
            ], [
                'to_branch_id.required' => 'يرجى اختيار الفرع المراد النقل إليه.',
                'to_branch_id.exists' => 'الفرع المحدد غير موجود.',
            ]);

            if ((int)$request->to_branch_id === (int)$student->branch_id) {
                return response()->json(['status' => 'error', 'message' => 'الفرع المستهدف يجب أن يكون مختلفاً عن الفرع الحالي للطالب.'], 422);
            }

            $docPath = null;
            if ($request->hasFile('document')) {
                $docPath = $request->file('document')->store("requests/transfer/{$student->id}", 'public');
            }

            $item = StudentTransfer::create([
                'student_id' => $student->id,
                'from_branch_id' => $student->branch_id,
                'to_branch_id' => $request->to_branch_id,
                'reason' => $request->reason,
                'status' => 'PENDING',
                'requested_by' => $user?->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم رفع طلب نقل الطالب من مدير الفرع، وبانتظار دراسة واعتماد رئيس قسم الدراسة والامتحانات وإفادة الشؤون التعليمية.',
                'data' => $item,
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'مسار طلب غير معروف.'], 400);
    }
}
