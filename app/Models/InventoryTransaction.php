<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToBranch;

class InventoryTransaction extends Model
{
    use HasFactory, BelongsToBranch;

    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'transaction_type',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'branch_id',
        'request_id',
        'created_by',
        'notes',
        'created_at',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
