<?php

namespace Tests\Concerns;

use App\Models\Student;
use App\Services\StudentDocumentService;

/**
 * قيد الطالب يشترط إرفاق كل المستندات الإلزامية في الطلب نفسه، فتضيفها الاختبارات التي تنشئ طلاباً عبر هذا الـ trait.
 */
trait AttachesAdmissionDocuments
{
    /** يضيف إلى بيانات القيد كل المستندات الإلزامية لتصنيف الجنسية والإعاقة الواردين فيها. */
    protected function withAdmissionDocuments(array $payload): array
    {
        $probe = new Student([
            'nationality_category' => $payload['nationality_category'] ?? 'LIBYAN',
            'has_disability' => $payload['has_disability'] ?? false,
        ]);

        $pdf = 'data:application/pdf;base64,' . base64_encode("%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF");

        $payload['attached_documents'] = array_map(
            fn (string $type) => ['type' => $type, 'name' => strtolower($type) . '.pdf', 'base64' => $pdf],
            app(StudentDocumentService::class)->requiredTypesFor($probe)
        );

        return $payload;
    }
}
