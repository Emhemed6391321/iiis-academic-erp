<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class OperationalWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'window_type',
        'title',
        'start_at',
        'end_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(OperationalWindowException::class, 'window_id');
    }

    /**
     * Check if the window is currently open for a given branch.
     */
    public function isOpenForBranch(?int $branchId = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        // 1. Check standard window
        if ($now->between($this->start_at, $this->end_at)) {
            return true;
        }

        // 2. If after standard end, check if branch has an active approved extension
        if ($branchId && $now->isAfter($this->end_at)) {
            $hasException = $this->exceptions()
                ->where('branch_id', $branchId)
                ->where('extended_until', '>=', $now)
                ->exists();

            if ($hasException) {
                return true;
            }
        }

        return false;
    }
}
