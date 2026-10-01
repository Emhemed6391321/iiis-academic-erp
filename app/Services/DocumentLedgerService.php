<?php

namespace App\Services;

use App\Models\DocumentVerification;
use App\Models\Student;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DocumentLedgerService
{
    /**
     * Generate a cryptographic verification entry for an official document.
     */
    public function issueDocument(
        string $documentType,
        Student $student,
        array $metadata = [],
        ?string $signatoryPosition = 'مدير عام المعهد / الشؤون التعليمية',
        ?int $issuedBy = null
    ): DocumentVerification {
        $documentUuid = (string) Str::uuid();
        $issueDate    = Carbon::now()->format('Y-m-d');
        $branchId     = $student->branch_id;

        // Strictly redact sensitive private information (National ID, Phone, Guardian)
        $sanitizedMetadata = [
            'document_title'   => $this->getDocumentTitleAr($documentType),
            'student_name'     => $student->full_name ?? ($student->first_name . ' ' . $student->family_name),
            'student_code'     => $student->student_code ?? $student->admission_number ?? 'STD-' . $student->id,
            'branch_name'      => $student->branch?->name ?? 'المركز الرئيسي',
            'department_name'  => $student->department?->name ?? 'عام',
            'study_year'       => $student->currentStudyYear?->name ?? 'السنة الدراسية الحالية',
            'academic_status'  => $student->academic_status ?? 'مقيد',
            'issue_date'       => $issueDate,
            'signatory'        => $signatoryPosition,
            'custom_fields'    => array_diff_key($metadata, array_flip([
                'national_id', 'phone', 'guardian_phone', 'mother_name', 'address', 'password', 'id_number'
            ])),
        ];

        // Generate tamper-evident SHA-256 hash signature
        $secretSalt = config('app.key') ?: 'manhal-secure-salt-2026';
        $hashMaterial = $documentUuid . '|' . $documentType . '|' . $student->id . '|' . $branchId . '|' . $issueDate . '|' . json_encode($sanitizedMetadata, JSON_UNESCAPED_UNICODE) . '|' . $secretSalt;
        $hashSignature = hash('sha256', $hashMaterial);

        return DocumentVerification::create([
            'document_uuid'      => $documentUuid,
            'document_type'      => $documentType,
            'student_id'         => $student->id,
            'branch_id'          => $branchId,
            'academic_year_id'   => $student->enrolled_academic_year_id ?? 1,
            'hash_signature'     => $hashSignature,
            'metadata_payload'   => $sanitizedMetadata,
            'issue_date'         => $issueDate,
            'signatory_position' => $signatoryPosition,
            'status'             => 'VALID',
        ]);
    }

    /**
     * Verify a document's authenticity and status via its public UUID.
     */
    public function verifyDocument(string $uuid): array
    {
        $verification = DocumentVerification::with('branch:id,name,code')
            ->where('document_uuid', $uuid)
            ->first();

        if (!$verification) {
            return [
                'exists'      => false,
                'status'      => 'NOT_FOUND',
                'is_valid'    => false,
                'message'     => 'لم يتم العثور على أي وثيقة مطابقة لهذا الرمز في السجل العام للمعهد.',
            ];
        }

        // Verify SHA-256 cryptographic integrity
        $secretSalt = config('app.key') ?: 'manhal-secure-salt-2026';
        $hashMaterial = $verification->document_uuid . '|' . $verification->document_type . '|' . $verification->student_id . '|' . $verification->branch_id . '|' . Carbon::parse($verification->issue_date)->format('Y-m-d') . '|' . json_encode($verification->metadata_payload, JSON_UNESCAPED_UNICODE) . '|' . $secretSalt;
        $computedHash = hash('sha256', $hashMaterial);

        $isTampered = !hash_equals($verification->hash_signature, $computedHash);

        if ($isTampered) {
            return [
                'exists'      => true,
                'status'      => 'TAMPERED',
                'is_valid'    => false,
                'message'     => 'تحذير أمني: البصمة الرقمية للوثيقة لا تتطابق مع سجلات التشفير المعتمدة.',
                'issued_at'   => $verification->issue_date,
            ];
        }

        if ($verification->revoked_at || $verification->status === 'REVOKED' || !$verification->isValid()) {
            return [
                'exists'         => true,
                'status'         => 'REVOKED',
                'is_valid'       => false,
                'message'        => 'تم إلغاء هذه الوثيقة رسمياً من قبل إدارة المعهد.',
                'revoked_at'     => $verification->revoked_at,
                'revocation_note'=> $verification->revocation_reason ?? 'إلغاء إداري معتمد',
                'document'       => $this->getSanitizedVerificationSummary($verification),
            ];
        }

        return [
            'exists'         => true,
            'status'         => 'VALID',
            'is_valid'       => true,
            'message'        => 'وثيقة رسمية أصلية ومعتمدة صادرة عن المعهد التخصصي للعلوم والمهن.',
            'document'       => $this->getSanitizedVerificationSummary($verification),
        ];
    }

    /**
     * Revoke an issued document.
     */
    public function revokeDocument(string $uuid, string $reason, ?int $revokedBy = null): bool
    {
        $verification = DocumentVerification::where('document_uuid', $uuid)->first();
        if (!$verification) {
            return false;
        }

        return $verification->update([
            'status'            => 'REVOKED',
            'revoked_at'        => Carbon::now(),
            'revocation_reason' => $reason,
            'revoked_by_id'     => $revokedBy,
        ]);
    }

    private function getSanitizedVerificationSummary(DocumentVerification $verification): array
    {
        $metadata = $verification->metadata_payload ?? [];

        return [
            'uuid'               => $verification->document_uuid,
            'document_type'      => $verification->document_type,
            'document_title'     => $metadata['document_title'] ?? $this->getDocumentTitleAr($verification->document_type),
            'student_name'       => $metadata['student_name'] ?? 'طالب مقيد',
            'student_code'       => $metadata['student_code'] ?? '—',
            'branch'             => $verification->branch?->name ?? $metadata['branch_name'] ?? 'الإدارة المركزية',
            'department'         => $metadata['department_name'] ?? '—',
            'study_year'         => $metadata['study_year'] ?? '—',
            'academic_status'    => $metadata['academic_status'] ?? 'مقيد',
            'issue_date'         => $verification->issue_date ? Carbon::parse($verification->issue_date)->format('Y-m-d') : null,
            'signatory_position' => $verification->signatory_position ?? 'إدارة المعهد',
            'hash_signature'     => substr($verification->hash_signature, 0, 16) . '...' . substr($verification->hash_signature, -16),
            'full_hash'          => $verification->hash_signature,
        ];
    }

    private function getDocumentTitleAr(string $type): string
    {
        return match (strtoupper($type)) {
            'ENROLLMENT_CERTIFICATE' => 'إفادة قيد دراسي رسمية',
            'CONDUCT_CERTIFICATE'    => 'شهادة حسن سيرة وسلوك',
            'TRANSCRIPT'              => 'كشف درجات وسجل أكاديمي',
            'STUDENT_CARD'           => 'بطاقة طالب إلكترونية',
            'GRADUATION_CERTIFICATE' => 'مصدقة تخرج رسمية',
            default                  => 'وثيقة أكاديمية رسمية',
        };
    }
}
