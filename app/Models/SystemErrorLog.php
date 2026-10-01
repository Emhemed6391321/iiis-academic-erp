<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemErrorLog extends Model
{
    use HasFactory;

    protected $table = 'system_error_logs';

    protected $fillable = [
        'error_id',
        'error_hash',
        'error_type',
        'severity',
        'message',
        'file',
        'line',
        'stack_trace',
        'url',
        'page_context',
        'button_action',
        'http_method',
        'user_id',
        'user_name',
        'branch_id',
        'branch_name',
        'ip_address',
        'user_agent',
        'request_data',
        'status',
        'occurrences_count',
        'first_seen_at',
        'last_seen_at',
        'resolved_by_user_id',
        'resolved_by_user_name',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'request_data' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
        'occurrences_count' => 'integer',
        'line' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
