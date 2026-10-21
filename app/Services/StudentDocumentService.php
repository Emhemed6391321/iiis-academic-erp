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
     * - ليبي / ليبي بدون رقم وطني: شهادة الميلاد، والشهادة الإعدادية، والبطاقة أو إثبات الهوية.
     * - غير الليبي: شهادة ميلاد لغير الليبيين، وصورة الجواز الملونة، والإقامة السارية.
     * - الشهادة الصحية مطلوبة لجميع الطلاب وفق الإجراء القائم.
     *
     * @return string[] مفاتيح DOCUMENT_TYPES
     */
    public function requiredTypesFor(Student $student): array
    {
        $common = ['HEALTH_CERT'];

        $byCategory = match ($student->nationality_category) {
            'OTHER' => ['FOREIGN_BIRTH_CERT', 'PASSPORT_COPY', 'RESIDENCE_PERMIT'],
            default => ['NATIONAL_ID_CARD', 'BIRTH_CERT', 'BASIC_EDUCATION_CERT'],
        };

        if ($student->has_disability) {
            $common[] = 'DISABILITY_MEDICAL_REP';
        }

        return array_values(array_unique(array_merge($byCategory, $common)));
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
