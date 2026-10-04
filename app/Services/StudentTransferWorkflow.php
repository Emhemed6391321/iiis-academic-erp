<?php

namespace App\Services;

use App\Models\StudentTransfer;
use App\Models\Student;
use App\Models\User;
use App\Models\SystemAuditTrail;
use App\Models\Scopes\BranchScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentTransferWorkflow
{
    // The 4 Official Handshake Stages
    public const STAGE_ORIGIN_REQUESTED       = 'ORIGIN_BRANCH_REQUESTED';
    public const STAGE_ACADEMIC_REVIEWED      = 'ACADEMIC_AFFAIRS_REVIEWED';
    public const STAGE_DESTINATION_ACCEPTED   = 'DESTINATION_BRANCH_ACCEPTED';
    public const STAGE_HQ_APPROVED            = 'HQ_FINAL_APPROVED';
    public const STAGE_REJECTED               = 'REJECTED';

    /**
     * Allowed State Transitions Matrix
     */
    protected const TRANSITIONS = [
        self::STAGE_ORIGIN_REQUESTED => [
            self::STAGE_ACADEMIC_REVIEWED,
            self::STAGE_REJECTED,
        ],
        self::STAGE_ACADEMIC_REVIEWED => [
            self::STAGE_DESTINATION_ACCEPTED,
            self::STAGE_REJECTED,
        ],
        self::STAGE_DESTINATION_ACCEPTED => [
            self::STAGE_HQ_APPROVED,
            self::STAGE_REJECTED,
        ],
        self::STAGE_HQ_APPROVED => [],
        self::STAGE_REJECTED => [],
    ];

    /**
     * Stage 1: Origin Branch requests transfer for its student.
     */
    public function initiateTransfer(Student $student, int $toBranchId, string $reason, User $requester): StudentTransfer
    {
        // Prevent transferring to the same branch
        if ((int)$student->branch_id === (int)$toBranchId) {
            throw ValidationException::withMessages([
                'to_branch_id' => 'الفرع المستهدف يجب أن يكون مختلفاً عن الفرع الحالي للطالب.',
            ]);
        }

        // Branch Manager can only request for students in their own branch
        if (!$requester->hasGlobalAccessScope() && $requester->branch_id && (int)$student->branch_id !== (int)$requester->branch_id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('غير مصرح لمدير الفرع بتقديم طلب نقل لطالب خارج فرعه.');
        }

        return DB::transaction(function () use ($student, $toBranchId, $reason, $requester) {
            $transfer = StudentTransfer::create([
                'student_id'      => $student->id,
                'from_branch_id'  => $student->branch_id,
                'to_branch_id'    => $toBranchId,
                'reason'          => $reason,
                'status'          => 'PENDING',
                'transfer_stage'  => self::STAGE_ORIGIN_REQUESTED,
                'requested_by'    => $requester->id,
            ]);

            SystemAuditTrail::log(
                eventType: 'STUDENT_TRANSFER_REQUESTED',
                description: "رفع طلب نقل الطالب ({$student->full_name}) من الفرع الحالي إلى فرع جديد.",
                payload: [
                    'transfer_id'    => $transfer->id,
                    'student_id'     => $student->id,
                    'from_branch_id' => $student->branch_id,
                    'to_branch_id'   => $toBranchId,
                    'stage'          => self::STAGE_ORIGIN_REQUESTED,
                ],
                userId: $requester->id,
                branchId: $student->branch_id
            );

            return $transfer;
        });
    }

    /**
     * Stage 2: Central Academic Affairs reviews and clears the transfer.
     */
    public function reviewAcademicAffairs(StudentTransfer $transfer, string $statement, bool $approve, User $reviewer): StudentTransfer
    {
        $this->validateTransition($transfer, $approve ? self::STAGE_ACADEMIC_REVIEWED : self::STAGE_REJECTED);

        // Four-Eyes principle: requester cannot review
        if ($transfer->requested_by && (int)$transfer->requested_by === (int)$reviewer->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('انتهاك مبدأ الرقابة الثنائية: لا يمكن لمنشئ طلب النقل مراجعته أو البت فيه.');
        }

        // Only Academic Affairs (Global scope)
        if (!$reviewer->hasGlobalAccessScope() && !$reviewer->hasPermission('APPROVE_STUDENT_STATUS')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('غير مصرح: مراجعة وإفادة النقل محصورة في إدارة الشؤون التعليمية المركزية.');
        }

        return DB::transaction(function () use ($transfer, $statement, $approve, $reviewer) {
            $now = Carbon::now();
            if ($approve) {
                $transfer->update([
                    'central_affairs_statement'   => $statement,
                    'central_affairs_approved_by' => $reviewer->id,
                    'central_affairs_approved_at' => $now,
                    'transfer_stage'              => self::STAGE_ACADEMIC_REVIEWED,
                ]);
            } else {
                $transfer->update([
                    'central_affairs_statement'   => $statement,
                    'central_affairs_approved_by' => $reviewer->id,
                    'central_affairs_approved_at' => $now,
                    'status'                      => 'REJECTED',
                    'transfer_stage'              => self::STAGE_REJECTED,
                ]);
            }

            SystemAuditTrail::log(
                eventType: 'STUDENT_TRANSFER_ACADEMIC_REVIEWED',
                description: "مراجعة الشؤون التعليمية المركزية لطلب نقل الطالب رقم #{$transfer->student_id}.",
                payload: [
                    'transfer_id' => $transfer->id,
                    'approved'    => $approve,
                    'stage'       => $transfer->transfer_stage,
                ],
                userId: $reviewer->id
            );

            return $transfer;
        });
    }

    /**
     * Stage 3: Destination branch accepts or rejects student admission.
     */
    public function acceptDestinationBranch(StudentTransfer $transfer, string $decisionNotes, bool $accept, User $destinationManager): StudentTransfer
    {
        $this->validateTransition($transfer, $accept ? self::STAGE_DESTINATION_ACCEPTED : self::STAGE_REJECTED);

        // Submitter cannot approve
        if ($transfer->requested_by && (int)$transfer->requested_by === (int)$destinationManager->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('انتهاك مبدأ الرقابة الثنائية: لا يمكن لمقدم الطلب قبول النقل.');
        }

        // Only manager of destination branch or global admin
        if (!$destinationManager->hasGlobalAccessScope() && (int)$destinationManager->branch_id !== (int)$transfer->to_branch_id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('غير مصرح: قرار قبول النقل محصور في إدارة الفرع المستقبل فقط.');
        }

        return DB::transaction(function () use ($transfer, $decisionNotes, $accept, $destinationManager) {
            $now = Carbon::now();
            if ($accept) {
                $transfer->update([
                    'receiving_branch_status'         => 'APPROVED',
                    'receiving_branch_decision_notes' => $decisionNotes,
                    'receiving_branch_decided_by'     => $destinationManager->id,
                    'receiving_branch_decided_at'     => $now,
                    'transfer_stage'                  => self::STAGE_DESTINATION_ACCEPTED,
                ]);
            } else {
                $transfer->update([
                    'receiving_branch_status'         => 'REJECTED',
                    'receiving_branch_decision_notes' => $decisionNotes,
                    'receiving_branch_decided_by'     => $destinationManager->id,
                    'receiving_branch_decided_at'     => $now,
                    'status'                          => 'REJECTED',
                    'transfer_stage'                  => self::STAGE_REJECTED,
                ]);
            }

            SystemAuditTrail::log(
                eventType: 'STUDENT_TRANSFER_DESTINATION_DECIDED',
                description: "قرار الفرع المستقبل بشأن قبول ضم الطالب رقم #{$transfer->student_id}: " . ($accept ? 'قبول' : 'رفض'),
                payload: [
                    'transfer_id' => $transfer->id,
                    'accepted'    => $accept,
                    'stage'       => $transfer->transfer_stage,
                ],
                userId: $destinationManager->id,
                branchId: $transfer->to_branch_id
            );

            return $transfer;
        });
    }

    /**
     * Stage 4: HQ Final Sovereign Approval & Automated Execution.
     */
    public function approveHQFinal(StudentTransfer $transfer, string $hqNotes, User $hqAdmin): StudentTransfer
    {
        $this->validateTransition($transfer, self::STAGE_HQ_APPROVED);

        // Four-eyes principle
        if ($transfer->requested_by && (int)$transfer->requested_by === (int)$hqAdmin->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('انتهاك مبدأ الرقابة الثنائية: لا يمكن لمنشئ الطلب اعتماده نهائياً.');
        }

        // Only HQ / Global Scope
        if (!$hqAdmin->hasGlobalAccessScope()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('غير مصرح: الاعتماد النهائي وتحديث تبعية الطالب محصور في الإدارة المركزية العامة.');
        }

        return DB::transaction(function () use ($transfer, $hqNotes, $hqAdmin) {
            $now = Carbon::now();

            $transfer->update([
                'status'               => 'APPROVED',
                'approved_by'          => $hqAdmin->id,
                'approved_at'          => $now,
                'hq_final_approved_by' => $hqAdmin->id,
                'hq_final_approved_at' => $now,
                'hq_decision_notes'    => $hqNotes,
                'transfer_stage'       => self::STAGE_HQ_APPROVED,
            ]);

            // Execute student branch update (bypassing branch scope)
            $student = Student::withoutGlobalScope(BranchScope::class)->lockForUpdate()->find($transfer->student_id);
            if ($student) {
                $oldBranchId = $student->branch_id;
                $student->update(['branch_id' => $transfer->to_branch_id]);

                DB::table('student_status_history')->insert([
                    'student_id' => $student->id,
                    'old_status' => 'BRANCH_' . $oldBranchId,
                    'new_status' => 'BRANCH_' . $transfer->to_branch_id,
                    'event_type' => 'TRANSFER',
                    'reason'     => 'نقل واعتماد نهائي لتبعية الطالب: ' . $transfer->reason . ' - ' . $hqNotes,
                    'changed_by' => $hqAdmin->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            SystemAuditTrail::log(
                eventType: 'STUDENT_TRANSFER_HQ_FINAL_APPROVED',
                description: "الاعتماد النهائي وتنفيذ نقل الطالب رقم #{$transfer->student_id} إلى الفرع الجديد.",
                payload: [
                    'transfer_id'   => $transfer->id,
                    'student_id'    => $transfer->student_id,
                    'new_branch_id' => $transfer->to_branch_id,
                    'stage'         => self::STAGE_HQ_APPROVED,
                ],
                userId: $hqAdmin->id
            );

            return $transfer;
        });
    }

    /**
     * Validate whether state transition is legitimate.
     */
    protected function validateTransition(StudentTransfer $transfer, string $targetStage): void
    {
        $currentStage = $transfer->transfer_stage ?? self::STAGE_ORIGIN_REQUESTED;
        $allowed = self::TRANSITIONS[$currentStage] ?? [];

        if (!in_array($targetStage, $allowed, true)) {
            throw ValidationException::withMessages([
                'stage' => "تسلسل الإجراءات غير مكتمل: لا يمكن الانتقال من ({$currentStage}) إلى ({$targetStage}).",
            ]);
        }
    }
}
