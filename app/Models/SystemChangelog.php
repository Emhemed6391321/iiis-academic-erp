<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemChangelog extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'title',
        'description',
        'type',
        'impact',
        'author',
        'commit_hash',
        'branch',
        'affected_modules',
        'tags',
        'is_published',
        'requires_migration',
        'deployed_at',
    ];

    protected $casts = [
        'affected_modules' => 'array',
        'tags' => 'array',
        'is_published' => 'boolean',
        'requires_migration' => 'boolean',
        'deployed_at' => 'datetime',
    ];

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'feature'     => '✨ ميزة جديدة',
            'fix'         => '🐛 إصلاح خطأ',
            'security'    => '🔒 تحسين الأمان',
            'performance' => '⚡ تحسين الأداء',
            'ui'          => '🎨 تحسين الواجهة',
            'breaking'    => '⚠️ تغيير جذري',
            default       => 'تحديث',
        };
    }

    public function getImpactLabelAttribute(): string
    {
        return match($this->impact) {
            'low'      => 'منخفض',
            'medium'   => 'متوسط',
            'high'     => 'عالي',
            'critical' => 'حرج',
            default    => 'متوسط',
        };
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
