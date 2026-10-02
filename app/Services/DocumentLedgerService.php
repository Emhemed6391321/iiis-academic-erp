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
     * Generate an HMAC-SHA256 digital signature entry for an official document.
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

        // Generate tamper-evident HMAC-SHA256 signature with secure application key
        $secretKey = config('app.document_signing_key') ?: config('app.key') ?: 'iiis-secure-hmac-key-2026';
        $hashMaterial = $documentUuid . '|' . $documentType . '|' . $student->id . '|' . $branchId . '|' . $issueDate . '|' . json_encode($sanitizedMetadata, JSON_UNESCAPED_UNICODE);
        $hashSignature = hash_hmac('sha256', $hashMaterial, $secretKey);

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

        // 1. Verify HMAC-SHA256 signature (with legacy SHA-256 fallback)
        $secretKey = config('app.document_signing_key') ?: config('app.key') ?: 'iiis-secure-hmac-key-2026';
        $hashMaterial = $verification->document_uuid . '|' . $verification->document_type . '|' . $verification->student_id . '|' . $verification->branch_id . '|' . Carbon::parse($verification->issue_date)->format('Y-m-d') . '|' . json_encode($verification->metadata_payload, JSON_UNESCAPED_UNICODE);

        $computedHmac = hash_hmac('sha256', $hashMaterial, $secretKey);
        $legacyHash = hash('sha256', $hashMaterial . '|' . $secretKey);

        $isTampered = !hash_equals($verification->hash_signature, $computedHmac) 
                   && !hash_equals($verification->hash_signature, $legacyHash);

        if ($isTampered) {
            return [
                'exists'      => true,
                'status'      => 'TAMPERED',
                'is_valid'    => false,
                'message'     => 'تحذير أمني: التوقيع الرقمي للوثيقة لا يتطابق مع مفاتيح التحقق الرسمية للمعهد.',
                'issued_at'   => $verification->issue_date,
            ];
        }

        // 2. Check REPLACED state
        if ($verification->status === 'REPLACED') {
            return [
                'exists'          => true,
                'status'          => 'REPLACED',
                'is_valid'        => false,
                'message'         => 'تم استبدال هذه الوثيقة رسمياً بإصدار أحدث ومحدث من قبل إدارة المعهد.',
                'replaced_at'     => $verification->revoked_at,
                'replacement_note'=> $verification->revocation_reason ?? 'تم استبدال الوثيقة وتحديث السجل',
                'document'        => $this->getSanitizedVerificationSummary($verification),
            ];
        }

        // 3. Check REVOKED state
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
            'message'        => 'وثيقة رسمية أصلية ومعتمدة وموقعة رقمياً صادرة عن المعهد التخصصي للدراسات الإسلامية.',
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

    /**
     * Replace an existing document with a newer version.
     */
    public function replaceDocument(string $oldUuid, string $reason, ?int $revokedBy = null, ?string $newUuid = null): bool
    {
        $verification = DocumentVerification::where('document_uuid', $oldUuid)->first();
        if (!$verification) {
            return false;
        }

        $note = $newUuid ? "تم استبدال الوثيقة بإصدار رقم [{$newUuid}]: {$reason}" : $reason;

        return $verification->update([
            'status'            => 'REPLACED',
            'revoked_at'        => Carbon::now(),
            'revocation_reason' => $note,
            'revoked_by_id'     => $revokedBy,
        ]);
    }

    private function getSanitizedVerificationSummary(DocumentVerification $verification): array
    {
        $metadata = $verification->metadata_payload ?? [];
        $rawName = $metadata['student_name'] ?? 'طالب مقيد';

        return [
            'uuid'               => $verification->document_uuid,
            'document_type'      => $verification->document_type,
            'document_title'     => $metadata['document_title'] ?? $this->getDocumentTitleAr($verification->document_type),
            'student_name'       => $this->maskStudentName($rawName),
            'student_code'       => $metadata['student_code'] ?? '—',
            'branch'             => $verification->branch?->name ?? $metadata['branch_name'] ?? 'الإدارة المركزية',
            'department'         => $metadata['department_name'] ?? '—',
            'study_year'         => $metadata['study_year'] ?? '—',
            'academic_status'    => $metadata['academic_status'] ?? 'مقيد',
            'issue_date'         => $verification->issue_date ? Carbon::parse($verification->issue_date)->format('Y-m-d') : null,
            'signatory_position' => $verification->signatory_position ?? 'إدارة المعهد',
            'signature_algorithm'=> 'HMAC-SHA256',
            'hash_signature'     => substr($verification->hash_signature, 0, 16) . '...' . substr($verification->hash_signature, -16),
            'full_hash'          => $verification->hash_signature,
        ];
    }

    /**
     * Mask student name for privacy in public verification (e.g. "م*** ع*** الفيتوري").
     */
    public function maskStudentName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName));
        if (empty($parts)) {
            return '—';
        }

        if (count($parts) === 1) {
            return mb_substr($parts[0], 0, 1) . '***';
        }

        $masked = [];
        $count = count($parts);

        foreach ($parts as $index => $part) {
            if ($index === 0 || $index === $count - 1) {
                // Show first letter of first name and last name
                $masked[] = mb_substr($part, 0, 1) . '***';
            } else {
                $masked[] = '***';
            }
        }

        return implode(' ', $masked);
    }

    public function maskName(string $fullName): string
    {
        return $this->maskStudentName($fullName);
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
