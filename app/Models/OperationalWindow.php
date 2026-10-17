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

    /**
     * Every window type the system enforces. S2_COURSEWORK is used by grade entry
     * for the second semester's coursework.
     */
    public const WINDOW_TYPES = [
        'REGISTRATION',
        'S1_COURSEWORK',
        'S2_COURSEWORK',
        'S1_FINAL',
        'S2_FINAL',
        'SECOND_ROUND',
    ];

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
     * Explicitly provision the missing window types for an academic year.
     *
     * Created windows are DISABLED with an empty time range, so provisioning never opens
     * a window on its own; HQ must set the real dates and enable it. Existing windows are
     * never modified.
     *
     * @return string[] the window types that were created
     */
    public static function provisionMissingForYear(int $academicYearId, ?int $createdBy = null): array
    {
        $existing = static::where('academic_year_id', $academicYearId)->pluck('window_type')->all();
        $created = [];

        foreach (self::WINDOW_TYPES as $windowType) {
            if (in_array($windowType, $existing, true)) {
                continue;
            }

            $now = Carbon::now();
            static::create([
                'academic_year_id' => $academicYearId,
                'window_type' => $windowType,
                'title' => 'فترة ' . $windowType . ' - بانتظار ضبط الإدارة العامة',
                'start_at' => $now,
                'end_at' => $now,
                'is_active' => false,
                'created_by' => $createdBy,
            ]);

            $created[] = $windowType;
        }

        return $created;
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
