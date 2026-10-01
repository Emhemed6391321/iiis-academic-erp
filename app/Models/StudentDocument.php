<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StudentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'document_type',
        'original_name',
        'file_size',
        'mime_type',
        'is_required',
        'file_path',
        'file_hash',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'file_size'   => 'integer',
    ];

    protected $appends = ['file_url', 'formatted_size', 'type_label'];

    public const DOCUMENT_TYPES = [
        'NATIONAL_ID_CARD'       => 'صورة من الرقم الوطني أو إثبات الهوية',
        'BIRTH_CERT'             => 'شهادة الميلاد أو مستخرج رسمي من شهادة الميلاد',
        'BASIC_EDUCATION_CERT'   => 'شهادة إتمام مرحلة التعليم الأساسي أو المؤهل المطلوب',
        'HEALTH_CERT'            => 'الشهادة الصحية أو الكشف الطبي',
        'DISABILITY_MEDICAL_REP' => 'التقرير الطبي الخاص بالإعاقة',
        'PERSONAL_PHOTO'         => 'صورة شخصية للطالب',
        'OTHER'                  => 'مستندات إضافية',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): string
    {
        if (!$this->file_path) {
            return '';
        }

        if (str_starts_with($this->file_path, 'secure_vault/')) {
            return app(\App\Services\SecureFileVaultService::class)->generateSignedUrl($this->file_path);
        }

        return Storage::url($this->file_path);
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) return '—';
        if ($this->file_size >= 1048576) {
            return number_format($this->file_size / 1048576, 2) . ' MB';
        }
        return number_format($this->file_size / 1024, 1) . ' KB';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }
}
