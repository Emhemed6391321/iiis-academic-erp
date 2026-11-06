<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Single source of truth for where the encrypted file vault lives on disk.
 *
 * SecureFileVaultService writes "secure_vault/..." to the `local` disk, whose
 * root is storage/app/private. Older code read storage/app/secure_vault, so
 * backups silently skipped every vault file. Use these helpers everywhere.
 */
final class SecureVaultPaths
{
    /** Real vault directory (local disk root + secure_vault). */
    public static function primary(): string
    {
        return Storage::disk('local')->path('secure_vault');
    }

    /** Legacy directory kept for files written before the disk root moved. */
    public static function legacy(): string
    {
        return storage_path('app/secure_vault');
    }
}
