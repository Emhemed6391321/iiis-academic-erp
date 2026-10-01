<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalWindowException extends Model
{
    use HasFactory;

    protected $fillable = [
        'window_id',
        'branch_id',
        'granted_by',
        'extended_until',
        'reason',
    ];

    protected $casts = [
        'extended_until' => 'datetime',
    ];

    public function window(): BelongsTo
    {
        return $this->belongsTo(OperationalWindow::class, 'window_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
