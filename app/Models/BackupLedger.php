<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class BackupLedger extends Model
{
    use HasFactory;

    protected $table = 'backups_ledger';

    protected $fillable = [
        'disk',
        'file_name',
        'file_path',
        'file_size_bytes',
        'sha256_checksum',
        'type',
        'status',
        'initiated_by',
        'is_encrypted',
        'encryption_algorithm',
        'database_driver',
        'included_tables_count',
        'included_files_count',
        'error_message',
        'manifest_metadata',
        'completed_at',
    ];

    protected $casts = [
        'file_size_bytes'       => 'integer',
        'is_encrypted'         => 'boolean',
        'included_tables_count' => 'integer',
        'included_files_count'  => 'integer',
        'manifest_metadata'     => 'array',
        'completed_at'          => 'datetime',
    ];

    /**
     * User who initiated manual backup (null if scheduled/system).
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->file_size_bytes;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Verify whether the physical file exists and matches the stored SHA-256 checksum.
     */
    public function verifyIntegrity(): array
    {
        $fullPath = storage_path('app/' . $this->file_path);

        if (!File::exists($fullPath)) {
            return [
                'valid'   => false,
                'status'  => 'FILE_NOT_FOUND',
                'message' => 'ملف النسخة الاحتياطية غير موجود على القرص المحدد.',
            ];
        }

        $calculatedHash = hash_file('sha256', $fullPath);
        $matches = hash_equals($this->sha256_checksum, $calculatedHash);

        return [
            'valid'           => $matches,
            'status'          => $matches ? 'VERIFIED' : 'CHECKSUM_MISMATCH',
            'stored_hash'     => $this->sha256_checksum,
            'calculated_hash' => $calculatedHash,
            'actual_size'     => File::size($fullPath),
            'message'         => $matches ? 'بصمة التشفير مطابقة وسليمة 100%' : 'تحذير أمني: البصمة الرقمية غير مطابقة (احتمال تلف أو عبث بالملف)',
        ];
    }
}
