<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * الجنسيات الأجنبية المعتمدة للتسجيل. تديرها الإدارة العامة، ولا تُحذف الجنسية بل تُوقف.
 */
class Nationality extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * أسماء الجنسيات المعتمدة بترتيبها، وهي ما تعرضه قائمة التسجيل.
     *
     * @return string[]
     */
    public static function activeNames(): array
    {
        return static::active()->orderBy('sort_order')->orderBy('name')->pluck('name')->all();
    }
}
