<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestDiscussion extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_type',
        'request_id',
        'user_id',
        'comment_text',
        'attachment_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
