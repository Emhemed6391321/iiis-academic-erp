<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdministrativeSetting extends Model
{
    protected $fillable = [
        'setting_group',
        'key',
        'value',
        'type',
        'label',
        'description',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
