<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\BelongsToBranch;
use Carbon\Carbon;

class Student extends Model
{
    use HasFactory, BelongsToBranch;

    protected $fillable = [
        'academic_number',
        'national_id',
        'branch_id',
        'department_id',
        'current_study_year_id',
        'first_name',
        'father_name',
        'grandfather_name',
        'family_name',
        'mother_name',
        'gender',
        'birth_date',
        'birth_place',
        'nationality',
        'phone',
        'guardian_phone',
        'study_type',
        'academic_status',
        'enrolled_academic_year_id',
        'approved_by',
        'approved_at',
        'notes',
        // حقول إضافية لملف الطالب الكامل
        'profile_photo_path',
        'is_special_needs',
        'special_needs_desc',
        'medical_report_path',
        'data_verified_by',
        'data_verified_at',
        // 30+ Quality Check & Profile fields
        'religion',
        'passport_number',
        'username',
        'email',
        'address',
        'guardian_name',
        'guardian_relationship',
        'emergency_contact',
        'bus_route',
        'registration_type',
        'previous_school',
        'previous_level',
        'health_status',
        'blood_type',
        'chronic_diseases',
        'allergies',
        'ministry_student_id',
        'digital_signature_path',
        'has_disability',
        'disability_type',
        'disability_details',
        'chronic_diseases_list',
        'national_id_doc',
        'birth_certificate_doc',
        'education_form_doc',
        'equivalency_doc',
    ];

    protected $casts = [
        'birth_date'       => 'date',
        'approved_at'      => 'datetime',
        'data_verified_at' => 'datetime',
        'is_special_needs' => 'boolean',
        'has_disability'   => 'boolean',
        'chronic_diseases_list' => 'array',
    ];

    protected $appends = ['full_name', 'name', 'age', 'status_label', 'study_type_label', 'profile_photo_url', 'birth_date_formatted'];

    // ===================================================================
    // Accessors
    // ===================================================================

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->father_name} {$this->grandfather_name} {$this->family_name}");
    }

    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : null;
    }

    /**
     * تاريخ الميلاد بصيغة DD/MM/YYYY مع السن (للعرض في الواجهة)
     */
    public function getBirthDateFormattedAttribute(): ?string
    {
        if (!$this->birth_date) return null;
        $d = Carbon::parse($this->birth_date);
        return $d->format('d/m/Y');
    }

    /**
     * رابط صورة الطالب — مُعالَج لتجنب تكرار storage/ في المسار
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo_path) return null;
        $path = str_replace('\\', '/', $this->profile_photo_path);
        // إزالة أي بادئة storage/ موجودة مسبقاً لتجنب التكرار
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }
        return '/storage/' . $path;
    }

    public function getStatusLabelAttribute(): string
    {
        $status = $this->academic_status ?? $this->enrollment_status ?? 'ENROLLED_ACTIVE';
        return match($status) {
            'NEW_DRAFT'         => 'مسودة جديدة',
            'PENDING_HQ'        => 'قيد اعتماد الإدارة',
            'REJECTED_REVISION' => 'مُعاد للتعديل',
            'ENROLLED_ACTIVE', 'ACTIVE', 'REGULAR' => 'مقيد نشط',
            'SUSPENDED'         => 'إيقاف قيد',
            'TRANSFERRED'       => 'منقول',
            'GRADUATED'         => 'خريج',
            'EXPELLED'          => 'مطرود',
            default             => (string)$status,
        };
    }

    public function getStudyTypeLabelAttribute(): string
    {
        $type = $this->study_type ?? 'REGULAR';
        return match($type) {
            'REGULAR'  => 'نظامي',
            'INTISAB'  => 'انتساب',
            default    => (string)$type,
        };
    }

    public function getIsDataVerifiedAttribute(): bool
    {
        return !empty($this->data_verified_at);
    }

    public function getIsDataApprovedAttribute(): bool
    {
        return !empty($this->approved_at);
    }

    // ===================================================================
    // Relations — Core
    // ===================================================================

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function currentStudyYear(): BelongsTo
    {
        return $this->belongsTo(StudyYear::class, 'current_study_year_id');
    }

    public function studyYear(): BelongsTo
    {
        return $this->belongsTo(StudyYear::class, 'current_study_year_id');
    }

    public function enrolledAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'enrolled_academic_year_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function dataVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'data_verified_by');
    }

    // ===================================================================
    // Relations — Documents & Lifecycle
    // ===================================================================

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(StudentTransfer::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    // ===================================================================
    // Relations — Student File Module (الجداول الجديدة)
    // ===================================================================

    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class)->latest();
    }

    public function behaviors(): HasMany
    {
        return $this->hasMany(StudentBehavior::class)->latest('violation_date');
    }

    public function excuses(): HasMany
    {
        return $this->hasMany(ExcuseRequest::class)->latest();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class, 'student_id')->latest('event_date');
    }

    public function enrollmentStatusRequests(): HasMany
    {
        return $this->hasMany(EnrollmentStatusRequest::class)->latest();
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(StudentAttendance::class)->latest('record_date');
    }
}
