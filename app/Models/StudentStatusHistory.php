<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentStatusHistory extends Model
{
    protected $table = 'student_status_history';

    protected $fillable = [
        'student_id', 'old_status', 'new_status',
        'event_type', 'reason', 'document_path',
        'meta', 'changed_by', 'event_date',
    ];

    protected $casts = [
        'meta'       => 'array',
        'event_date' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Map event type to Arabic label and icon color for timeline display.
     */
    public function getTimelineMetaAttribute(): array
    {
        return match($this->event_type) {
            'RENEWAL'      => ['label' => 'تجديد قيد', 'color' => 'green',  'icon' => 'arrow-path'],
            'PAUSE'        => ['label' => 'إيقاف قيد', 'color' => 'gray',   'icon' => 'pause-circle'],
            'TRANSFER'     => ['label' => 'نقل فرع',   'color' => 'blue',   'icon' => 'arrows-right-left'],
            'PROMOTION'    => ['label' => 'ترحيل',     'color' => 'emerald','icon' => 'academic-cap'],
            'SYSTEM_CHANGE'=> ['label' => 'تغيير صفة', 'color' => 'purple', 'icon' => 'pencil-square'],
            'WITHDRAWAL'        => ['label' => 'سحب ملف',   'color' => 'slate',  'icon' => 'archive-box'],
            'DEPT_CHANGE'       => ['label' => 'تغيير شعبة','color' => 'indigo', 'icon' => 'folder-open'],
            'DOCUMENT_UPLOAD'   => ['label' => 'إرفاق مستند','color' => 'teal',   'icon' => 'document-arrow-up'],
            'DOCUMENT_REPLACE'  => ['label' => 'استبدال مستند','color' => 'amber','icon' => 'arrow-path'],
            'DOCUMENT_DELETE'   => ['label' => 'حذف مستند', 'color' => 'rose',   'icon' => 'trash'],
            'DATA_UPDATE'       => ['label' => 'تعديل بيانات الطالب', 'color' => 'blue', 'icon' => 'pencil-square'],
            default             => ['label' => 'تغيير حالة','color' => 'red',    'icon' => 'user-circle'],
        };
    }
}
