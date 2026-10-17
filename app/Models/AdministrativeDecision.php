<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdministrativeDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'decision_number',
        'request_id',
        'outcome',
        'issued_by',
        'issued_at',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(AdministrativeRequest::class, 'request_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
