<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'section_key',
        'section_name',
        'category',
        'category_label',
        'rating',
        'title',
        'description',
        'browser_info',
        'url',
        'ip_address',
        'status',
        'admin_notes',
        'resolved_by',
        'resolved_at',
        'priority',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'rating' => 'integer',
        'priority' => 'integer',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'     => 'قيد الانتظار',
            'in_progress' => 'تحت المعالجة',
            'resolved'    => 'تمت المعالجة',
            'dismissed'   => 'تم الرفض',
            default       => 'غير معروف',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending'     => 'amber',
            'in_progress' => 'blue',
            'resolved'    => 'emerald',
            'dismissed'   => 'slate',
            default       => 'slate',
        };
    }

    public function getCategoryLabelArabicAttribute(): string
    {
        return match($this->category) {
            'ui'          => 'واجهة المستخدم',
            'data'        => 'بيانات غير صحيحة',
            'performance' => 'بطء في الأداء',
            'access'      => 'مشكلة صلاحيات',
            'calculation' => 'خطأ في الحسابات',
            'other'       => 'أخرى',
            default       => $this->category_label ?? 'أخرى',
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }
}
