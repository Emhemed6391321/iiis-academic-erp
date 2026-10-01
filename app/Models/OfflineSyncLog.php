<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflineSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_uuid',
        'branch_id',
        'user_id',
        'total_records',
        'synced_count',
        'conflicts_count',
        'skipped_count',
        'client_device_info',
        'sync_status',
    ];

    protected $casts = [
        'total_records'   => 'integer',
        'synced_count'    => 'integer',
        'conflicts_count' => 'integer',
        'skipped_count'   => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
