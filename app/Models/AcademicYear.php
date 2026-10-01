<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_locked',
        'status',
        'activated_by',
        'activated_at',
        'locked_by',
        'locked_at',
        'notes',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'is_current'   => 'boolean',
        'is_locked'    => 'boolean',
        'activated_at' => 'datetime',
        'locked_at'    => 'datetime',
    ];

    // ===================================================================
    // Relationships
    // ===================================================================

    public function operationalWindows(): HasMany
    {
        return $this->hasMany(OperationalWindow::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'enrolled_academic_year_id');
    }

    public function gradeBatches(): HasMany
    {
        return $this->hasMany(GradeBatch::class);
    }

    /** مقررات هذا العام الدراسي */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    // ===================================================================
    // Scopes
    // ===================================================================

    /** العام الدراسي النشط الحالي */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true)->where('is_locked', false);
    }

    /** الأعوام المفتوحة للتعديل */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_locked', false);
    }

    // ===================================================================
    // Helper Methods
    // ===================================================================

    /** هل هذا العام نشط (معتمد وغير مقفل)؟ */
    public function isActive(): bool
    {
        return (bool) $this->is_current && !$this->is_locked;
    }

    /** هل هذا العام مقفل نهائياً؟ */
    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    /** هل يمكن إجراء تعديلات (إضافة طلاب أو درجات) في هذا العام؟ */
    public function isOpen(): bool
    {
        return $this->is_current && !$this->is_locked;
    }

    /** الحصول على العام الدراسي النشط حالياً (static) */
    public static function current(): ?self
    {
        return static::where('is_current', true)->where('is_locked', false)->first();
    }

    /** الحصول على العام النشط أو رمي خطأ إذا لم يوجد */
    public static function currentOrFail(): self
    {
        $year = static::current();
        if (!$year) {
            throw new \RuntimeException('لا يوجد عام دراسي نشط ومعتمد في النظام. يرجى تفعيل عام دراسي أولاً.');
        }
        return $year;
    }

    /** إحصائيات العام */
    public function getStats(): array
    {
        return [
            'students_count'     => $this->students()->count(),
            'grade_batches'      => $this->gradeBatches()->count(),
            'approved_batches'   => $this->gradeBatches()->where('status', 'HQ_APPROVED')->count(),
            'courses_count'      => $this->courses()->count(),
            'active_courses'     => $this->courses()->where('is_active', true)->count(),
        ];
    }

    /** تسمية الحالة بالعربية */
    public function getStatusLabelAttribute(): string
    {
        $status = $this->attributes['status'] ?? ($this->is_locked ? 'LOCKED' : ($this->is_current ? 'ACTIVE' : 'DRAFT'));
        return match ($status) {
            'DRAFT'    => 'مسودة — لم يُعتمد بعد',
            'ACTIVE'   => 'نشط ومعتمد',
            'LOCKED'   => 'مغلق نهائياً',
            'ARCHIVED' => 'مؤرشف',
            default    => $status,
        };
    }
}
