<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * حفظ مستندات الطالب الرسمية في الخزنة المشفرة وربطها بملفه.
 *
 * يُستخدم من مسار رفع المستندات من ملف الطالب ومن نموذج القبول الموحد، حتى تكون
 * كل المستندات محفوظة بالطريقة نفسها ومصنّفة بمفاتيح الأنواع المعتمدة (DOCUMENT_TYPES).
 */
class StudentDocumentService
{
    /** أسماء المستندات كما تظهر في نموذج القبول → مفتاح النوع المعتمد */
    public const LABEL_TO_TYPE = [
        'شهادة إتمام المرحلة الأساسية' => 'BASIC_EDUCATION_CERT',
        'شهادة ميلاد بالرقم الوطني' => 'BIRTH_CERT',
        'شهادة ميلاد لغير الليبيين' => 'FOREIGN_BIRTH_CERT',
        'التقرير الطبي الخاص بالإعاقة' => 'DISABILITY_MEDICAL_REP',
        'التقرير الطبي' => 'DISABILITY_MEDICAL_REP',
        'صورة من الرقم الوطني' => 'NATIONAL_ID_CARD',
        'صورة شخصية للطالب' => 'PERSONAL_PHOTO',
    ];

    /** الحد الأقصى لحجم أي مستند (10 ميجابايت) */
    public const MAX_BYTES = 10 * 1024 * 1024;

    public function __construct(private SecureFileVaultService $vault)
    {
    }

    /**
     * يحوّل اسم المستند (مفتاح نوع أو عنوان عربي) إلى نوع معتمد.
     * أي عنوان غير معروف يُصنَّف «مستندات إضافية» ويُحفظ عنوانه في الملاحظات.
     */
    public static function resolveType(?string $typeOrLabel): array
    {
        $value = trim((string) $typeOrLabel);

        if (array_key_exists($value, StudentDocument::DOCUMENT_TYPES)) {
            return [$value, null];
        }

        if (isset(self::LABEL_TO_TYPE[$value])) {
            return [self::LABEL_TO_TYPE[$value], null];
        }

        return ['OTHER', $value !== '' ? $value : null];
    }

    /**
     * المستندات الإلزامية بحسب تصنيف جنسية الطالب (الخادم هو المرجع).
     * - الليبي: الشهادة الإعدادية، وشهادة الميلاد بالرقم الوطني.
     * - غير الليبي: الشهادة الإعدادية، وصورة الجواز، وصورة الإقامة، وشهادة الميلاد.
     * بقية الأنواع (الشهادة الصحية، الصورة الشخصية، صورة الرقم الوطني، …) متاحة للرفع كمستندات اختيارية.
     * التقرير الطبي مطلوب فقط لمن سُجّلت له إعاقة.
     *
     * @return string[] مفاتيح DOCUMENT_TYPES
     */
    public function requiredTypesFor(Student $student): array
    {
        $required = match ($student->nationality_category) {
            'OTHER' => ['BASIC_EDUCATION_CERT', 'PASSPORT_COPY', 'RESIDENCE_PERMIT', 'FOREIGN_BIRTH_CERT'],
            default => ['BASIC_EDUCATION_CERT', 'BIRTH_CERT'],
        };

        if ($student->has_disability) {
            $required[] = 'DISABILITY_MEDICAL_REP';
        }

        return array_values(array_unique($required));
    }

    /**
     * المستندات الإلزامية التي لم تُرفع بعد. قائمة فارغة تعني أن الملف مكتمل.
     *
     * @return string[]
     */
    public function missingRequiredTypes(Student $student): array
    {
        $uploaded = StudentDocument::where('student_id', $student->id)->pluck('document_type')->all();

        return array_values(array_diff($this->requiredTypesFor($student), $uploaded));
    }

    /** الحقول الإلزامية في ملف الطالب قبل الاعتماد → عنوانها للمستخدم */
    public const APPROVAL_REQUIRED_FIELDS = [
        'first_name' => 'الاسم الأول',
        'father_name' => 'اسم الأب',
        'grandfather_name' => 'اسم الجد',
        'family_name' => 'اللقب',
        'mother_name' => 'اسم الأم',
        'gender' => 'الجنس',
        'birth_date' => 'تاريخ الميلاد',
        'birth_place' => 'مكان الميلاد',
        'phone' => 'رقم الهاتف',
        'guardian_phone' => 'هاتف ولي الأمر',
        'department_id' => 'القسم',
        'study_type' => 'نوع الدراسة',
        'branch_id' => 'الفرع',
        'nationality_category' => 'تصنيف الجنسية',
    ];

    /**
     * كل ما يمنع اعتماد الطالب: حقول فارغة، ومستندات إلزامية غير مرفقة أو ملفاتها غير موجودة في الخزنة.
     * هذا هو المرجع الوحيد لشروط الاعتماد، وتستدعيه كل مسارات الاعتماد في الخادم.
     *
     * @return array{missing_fields: string[], missing_documents: string[], unreadable_documents: string[]}
     */
    public function approvalBlockers(Student $student): array
    {
        $missingFields = [];
        foreach (self::APPROVAL_REQUIRED_FIELDS as $field => $label) {
            if (blank($student->{$field})) {
                $missingFields[] = $label;
            }
        }
        if ($student->nationality_category === 'LIBYAN' && blank($student->national_id)) {
            $missingFields[] = 'الرقم الوطني';
        }

        $docs = StudentDocument::where('student_id', $student->id)->get()->keyBy('document_type');
        $missingDocs = [];
        $unreadable = [];
        foreach ($this->requiredTypesFor($student) as $type) {
            $label = StudentDocument::DOCUMENT_TYPES[$type] ?? $type;
            $doc = $docs->get($type);
            if (!$doc) {
                $missingDocs[] = $label;
            } elseif (blank($doc->file_path) || !Storage::disk('local')->exists($doc->file_path)) {
                $unreadable[] = $label;
            }
        }

        return [
            'missing_fields' => $missingFields,
            'missing_documents' => $missingDocs,
            'unreadable_documents' => $unreadable,
        ];
    }

    /**
     * رسالة عربية بالنواقص، أو null إذا كان الملف مستوفياً. تُسجَّل المحاولة المرفوضة في سجل التدقيق.
     */
    public function approvalBlockMessage(Student $student, string $path): ?string
    {
        $b = $this->approvalBlockers($student);
        if (!array_filter($b)) {
            return null;
        }

        $parts = [];
        if ($b['missing_fields']) {
            $parts[] = 'بيانات ناقصة: ' . implode('، ', $b['missing_fields']);
        }
        if ($b['missing_documents']) {
            $parts[] = 'مستندات غير مرفقة: ' . implode('، ', $b['missing_documents']);
        }
        if ($b['unreadable_documents']) {
            $parts[] = 'مستندات مرفقة تعذر الوصول لملفها (أعد رفعها): ' . implode('، ', $b['unreadable_documents']);
        }

        \App\Models\SystemAuditTrail::log(
            eventType: 'STUDENT_APPROVAL_BLOCKED',
            description: "منع اعتماد الطالب (المرجع {$student->id}) لعدم استكمال المتطلبات عبر {$path}.",
            payload: $b + ['path' => $path],
            userId: \Illuminate\Support\Facades\Auth::id(),
            branchId: $student->branch_id,
            modelType: Student::class,
            modelId: $student->id,
            severity: 'WARNING'
        );

        return 'تعذر اعتماد ملف الطالب لعدم استكمال المتطلبات الإلزامية. يرجى مراجعة البيانات والمستندات الناقصة واستكمالها قبل إعادة الاعتماد. — ' . implode(' | ', $parts) . '.';
    }

    /**
     * يحفظ المستند في الخزنة ثم يربطه بالطالب.
     * عند وجود مستند من النوع نفسه يُستبدل، ولا يُحذف القديم إلا بعد نجاح حفظ الجديد.
     *
     * @param  UploadedFile|string  $input  ملف مرفوع أو نص Base64 بصيغة data URI
     * @return array{success: bool, message: string, document: ?StudentDocument, replaced: bool}
     */
    public function store(Student $student, string $documentType, UploadedFile|string $input, string $originalName, ?string $notes, ?int $uploadedBy): array
    {
        $vaultResult = $this->vault->storeSecure($input, 'students/documents', $student->branch_id);
        if (!$vaultResult['success']) {
            return ['success' => false, 'message' => $vaultResult['error'], 'document' => null, 'replaced' => false];
        }

        $existing = $documentType === 'OTHER'
            ? null
            : StudentDocument::where('student_id', $student->id)->where('document_type', $documentType)->first();

        $fileSize = $input instanceof UploadedFile
            ? $input->getSize()
            : (int) (strlen((string) $input) * 0.75);

        $isRequired = in_array($documentType, $this->requiredTypesFor($student), true);

        $document = StudentDocument::create([
            'student_id' => $student->id,
            'document_type' => $documentType,
            'original_name' => $originalName,
            'file_size' => $fileSize,
            'mime_type' => $vaultResult['mime'],
            'is_required' => $isRequired,
            'file_path' => $vaultResult['path'],
            'file_hash' => $vaultResult['hash'],
            'notes' => $notes,
            'uploaded_by' => $uploadedBy ?? 1,
        ]);

        if ($documentType === 'PERSONAL_PHOTO') {
            $student->update(['profile_photo_path' => $vaultResult['path']]);
        } elseif ($documentType === 'DISABILITY_MEDICAL_REP') {
            $student->update(['medical_report_path' => $vaultResult['path']]);
        }

        if ($existing) {
            $this->deleteStoredFile($existing);
            $existing->delete();
        }

        return ['success' => true, 'message' => 'تم حفظ المستند وربطه بملف الطالب.', 'document' => $document, 'replaced' => (bool) $existing];
    }

    /**
     * يحذف الملف المخزّن فعلياً (الخزنة المشفرة أو القرص العام للصور القديمة).
     */
    public function deleteStoredFile(StudentDocument $document): void
    {
        if (!$document->file_path) {
            return;
        }

        if (str_starts_with($document->file_path, 'secure_vault/')) {
            Storage::disk('local')->delete($document->file_path);
        } else {
            Storage::disk('public')->delete($document->file_path);
        }
    }
}
