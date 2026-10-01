<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentStatusHistory;
use App\Models\EnrollmentStatusRequest;
use App\Models\SystemAuditTrail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DomainException;
use InvalidArgumentException;

class StudentStateMachineService
{
    protected SecureFileVaultService $vault;

    public function __construct(SecureFileVaultService $vault)
    {
        $this->vault = $vault;
    }

    /**
     * Legal State Transitions Matrix:
     * Current State => [ Allowed Target States ]
     */
    public const STATE_TRANSITIONS = [
        'NEW_DRAFT' => [
            'PENDING_HQ',      // الفرع يرفع الملف للاعتماد المركزي
        ],
        'PENDING_HQ' => [
            'ENROLLED_ACTIVE',   // الإدارة العامة تعتمد القيد وتصدر الرقم الأكاديمي
            'REJECTED_REVISION', // الإدارة العامة ترفض أو تعيد الملف للفرع للاستيفاء
        ],
        'REJECTED_REVISION' => [
            'PENDING_HQ',      // الفرع يعيد رفع الملف بعد تصحيح النواقص
        ],
        'ENROLLED_ACTIVE' => [
            'SUSPENDED',       // إيقاف قيد مؤقت (بطلب رسمي)
            'TRANSFERRED',     // نقل تبعية لفرع آخر
            'GRADUATED',       // إتمام متطلبات التخرج
            'EXPELLED',        // شطب / فصل نهائي (تجاوز غياب أو قرار تأديبي)
        ],
        'SUSPENDED' => [
            'ENROLLED_ACTIVE',   // تجديد قيد بعد انتهاء فترة الإيقاف
            'EXPELLED',        // فصل نهائي إذا انقضت مهلة التجديد القانونية
        ],
        'TRANSFERRED' => [],   // حالة طرفية (يتم متابعة الطالب برقم القيد في الفرع المستقبل)
        'GRADUATED'   => [],   // حالة طرفية نهائية
        'EXPELLED'    => [],   // حالة طرفية نهائية
    ];

    /**
     * Get allowed target states for a student.
     */
    public function getAllowedTransitions(string $currentStatus): array
    {
        return self::STATE_TRANSITIONS[$currentStatus] ?? [];
    }

    /**
     * Verify if a transition is legal for the student and authorized for the user.
     * 
     * @return array{allowed: bool, reason: string|null}
     */
    public function canTransition(Student $student, string $targetStatus, ?User $user = null): array
    {
        $current = $student->academic_status;

        if ($current === $targetStatus) {
            return [
                'allowed' => false,
                'reason' => "الطالب مسجل بهذه الحالة الأكاديمية حالياً ({$current}).",
            ];
        }

        $allowedTargets = $this->getAllowedTransitions($current);
        if (!in_array($targetStatus, $allowedTargets, true)) {
            return [
                'allowed' => false,
                'reason' => "انتقال غير مشروع أمنياً ومخالف للائحة الأكاديمية: لا يمكن الانتقال مباشرة من حالة [{$current}] إلى [{$targetStatus}].",
            ];
        }

        // Scope Authorization (Option B):
        // Branch users can only promote NEW_DRAFT/REJECTED_REVISION to PENDING_HQ.
        // Direct transition to ENROLLED_ACTIVE, SUSPENDED, GRADUATED, EXPELLED requires HQ Central Authority.
        $user = $user ?? Auth::user();

        if ($user && !$user->hasGlobalAccessScope()) {
            $isBranchAllowedDirect = (
                ($current === 'NEW_DRAFT' && $targetStatus === 'PENDING_HQ') ||
                ($current === 'REJECTED_REVISION' && $targetStatus === 'PENDING_HQ')
            );

            if (!$isBranchAllowedDirect) {
                return [
                    'allowed' => false,
                    'reason' => 'غير مصرح: مستخدمو الفروع لا يملكون صلاحية التعديل المباشر لحالة القيد. يجب رفع طلب تعديل حالة رسمي مصحوب بمرفق لاعتماده حصرياً من الإدارة العامة (شؤون الطلاب المركزية).',
                ];
            }
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Execute state transition with strict transaction, history logging, and audit trail.
     * 
     * @throws DomainException
     */
    public function transition(
        Student $student,
        string $targetStatus,
        string $reason,
        ?string $documentPath = null,
        ?User $user = null,
        array $meta = []
    ): Student {
        $user = $user ?? Auth::user();
        $check = $this->canTransition($student, $targetStatus, $user);

        if (!$check['allowed']) {
            throw new DomainException($check['reason']);
        }

        $oldStatus = $student->academic_status;
        $now = Carbon::now();

        return DB::transaction(function () use ($student, $oldStatus, $targetStatus, $reason, $documentPath, $user, $meta, $now) {
            $updateData = [
                'academic_status' => $targetStatus,
            ];

            // If HQ approves enrollment
            if ($oldStatus === 'PENDING_HQ' && $targetStatus === 'ENROLLED_ACTIVE') {
                $updateData['approved_by'] = $user?->id;
                $updateData['approved_at'] = $now;
            }

            $student->update($updateData);

            // 1. Record in immutable Student Status History
            StudentStatusHistory::create([
                'student_id'    => $student->id,
                'event_type'    => $this->mapEventType($oldStatus, $targetStatus),
                'old_status'    => $oldStatus,
                'new_status'    => $targetStatus,
                'reason'        => $reason,
                'document_path' => $documentPath,
                'meta'          => !empty($meta) ? $meta : null,
                'changed_by'    => $user?->id,
                'event_date'    => $now,
            ]);

            // 2. Cryptographic Tamper-Evident Audit Trail
            SystemAuditTrail::log(
                eventType: 'STUDENT_STATUS_TRANSITION',
                description: "انتقال حالة قيد الطالب [{$student->full_name}] من ({$oldStatus}) إلى ({$targetStatus}). السبب: {$reason}",
                payload: [
                    'student_id'    => $student->id,
                    'academic_no'   => $student->academic_number,
                    'old_status'    => $oldStatus,
                    'new_status'    => $targetStatus,
                    'reason'        => $reason,
                    'document_path' => $documentPath,
                    'meta'          => $meta,
                ],
                userId: $user?->id,
                branchId: $student->branch_id,
                modelType: Student::class,
                modelId: $student->id,
                severity: in_array($targetStatus, ['EXPELLED', 'SUSPENDED']) ? 'CRITICAL' : 'WARNING'
            );

            return $student->fresh();
        });
    }

    /**
     * Submit a status change request from BRANCH_SCOPE (Option B).
     */
    public function submitStatusChangeRequest(
        Student $student,
        string $requestedStatus,
        string $reason,
        mixed $documentFile,
        User $requester
    ): EnrollmentStatusRequest {
        if (empty($reason) || mb_strlen($reason) < 10) {
            throw new InvalidArgumentException('يجب تقديم بيان وسبب إداري واضح لا يقل عن 10 أحرف.');
        }

        if (!$documentFile) {
            throw new InvalidArgumentException('يجب إرفاق مستند أو قرار رسمي معتمد كمسوّغ لطلب تعديل الحالة.');
        }

        // Store document securely in the private vault
        $vaultResult = $this->vault->storeSecure($documentFile, "students/{$student->id}/status_requests", $student->branch_id);
        if (!$vaultResult['success']) {
            throw new InvalidArgumentException($vaultResult['error'] ?? 'تعذر حفظ المستند المرفق.');
        }

        $requestType = match ($requestedStatus) {
            'SUSPENDED'       => 'PAUSE',
            'ENROLLED_ACTIVE' => 'RENEWAL',
            default           => 'PAUSE',
        };

        return DB::transaction(function () use ($student, $requestType, $reason, $vaultResult, $requester) {
            $req = EnrollmentStatusRequest::create([
                'student_id'              => $student->id,
                'request_type'            => $requestType,
                'target_academic_year_id' => $student->enrolled_academic_year_id,
                'reason'                  => $reason,
                'document_path'           => $vaultResult['path'],
                'branch_status'           => 'APPROVED', // الفرع يرفع الطلب بالموافقة
                'hq_status'               => 'PENDING',
                'final_status'            => 'PENDING',
                'created_by'              => $requester->id,
                'branch_reviewed_by'      => $requester->id,
            ]);

            SystemAuditTrail::log(
                eventType: 'STUDENT_STATUS_REQUEST_SUBMITTED',
                description: "رفع طلب تعديل حالة قيد الطالب [{$student->full_name}] إلى ({$requestType}) من قبل الفرع.",
                payload: [
                    'request_id'     => $req->id,
                    'student_id'     => $student->id,
                    'request_type'   => $requestType,
                    'reason'         => $reason,
                    'document_vault' => $vaultResult['path'],
                ],
                userId: $requester->id,
                branchId: $student->branch_id,
                modelType: EnrollmentStatusRequest::class,
                modelId: $req->id,
                severity: 'WARNING'
            );

            return $req;
        });
    }

    /**
     * Map old and new status to status history event type.
     */
    private function mapEventType(string $old, string $new): string
    {
        if ($new === 'PENDING_HQ') return 'SUBMISSION_FOR_APPROVAL';
        if ($new === 'ENROLLED_ACTIVE' && $old === 'PENDING_HQ') return 'REGISTRATION_APPROVED';
        if ($new === 'ENROLLED_ACTIVE' && $old === 'SUSPENDED') return 'RENEWAL';
        if ($new === 'SUSPENDED') return 'PAUSE';
        if ($new === 'TRANSFERRED') return 'TRANSFER';
        if ($new === 'GRADUATED') return 'GRADUATION';
        if ($new === 'EXPELLED') return 'EXPULSION';
        if ($new === 'REJECTED_REVISION') return 'REVISION_REQUESTED';

        return 'STATUS_CHANGE';
    }
}
