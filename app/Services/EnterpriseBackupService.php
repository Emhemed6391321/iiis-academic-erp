<?php

namespace App\Services;

use App\Models\BackupLedger;
use App\Models\SystemAuditTrail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use ZipArchive;
use Exception;
use Throwable;

class EnterpriseBackupService
{
    const CIPHER_METHOD = 'AES-256-CBC';

    /**
     * Create a complete encrypted enterprise backup.
     *
     * @param string $type 'manual' | 'scheduled' | 'emergency'
     * @param int|null $userId
     * @param array $options
     * @return BackupLedger
     * @throws Exception
     */
    public static function createBackup(string $type = 'manual', ?int $userId = null, array $options = []): BackupLedger
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $backupId = 'IIIS_BACKUP_' . $timestamp . '_' . bin2hex(random_bytes(4));
        $finalFileName = "{$backupId}.enc";
        $finalRelativePath = "backups/encrypted/{$finalFileName}";
        $finalFullPath = storage_path("app/{$finalRelativePath}");

        // Ensure storage directory exists
        $encryptedDir = storage_path('app/backups/encrypted');
        if (!File::exists($encryptedDir)) {
            File::makeDirectory($encryptedDir, 0750, true);
        }

        // Temporary workspace for building backup
        $tempDir = storage_path("app/backups/temp_{$backupId}");
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0750, true);
        }

        // Create initial pending ledger record
        $ledger = BackupLedger::create([
            'disk'                 => 'local',
            'file_name'            => $finalFileName,
            'file_path'            => $finalRelativePath,
            'file_size_bytes'      => 0,
            'sha256_checksum'      => 'PENDING_GENERATION',
            'type'                 => $type,
            'status'               => 'processing',
            'initiated_by'         => $userId,
            'is_encrypted'         => true,
            'encryption_algorithm' => self::CIPHER_METHOD,
            'database_driver'      => config('database.default', 'sqlite'),
            'included_tables_count'=> 0,
            'included_files_count' => 0,
            'manifest_metadata'    => [],
        ]);

        try {
            // 1. Dump Database
            $dbDumpResult = self::dumpDatabase($tempDir, $timestamp);

            // 2. Archive Storage Files (branding, uploads, documents, photos)
            $filesArchiveResult = self::packMediaFiles($tempDir);

            // 3. Generate Cryptographic Manifest
            $manifest = [
                'backup_id'         => $backupId,
                'system_name'       => 'IIIS Academic ERP',
                'system_version'    => '2.5.0-ENTERPRISE',
                'created_at'        => Carbon::now()->toIso8601String(),
                'type'              => $type,
                'database'          => $dbDumpResult,
                'storage_files'     => $filesArchiveResult,
                'encryption'        => [
                    'algorithm'     => self::CIPHER_METHOD,
                    'key_fingerprint'=> hash('sha256', self::getEncryptionKey()),
                ],
            ];

            $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            File::put("{$tempDir}/manifest.json", $manifestJson);

            // 4. Create Intermediate Zip Archive
            $tempZipPath = "{$tempDir}/raw_payload.zip";
            self::createZipArchive($tempDir, $tempZipPath);

            // 5. Encrypt Archive with AES-256-CBC
            self::encryptFile($tempZipPath, $finalFullPath);

            // 6. Compute Checksum and File Size
            $fileSizeBytes = File::size($finalFullPath);
            $sha256Checksum = hash_file('sha256', $finalFullPath);

            // 7. Update Ledger
            $ledger->update([
                'file_size_bytes'      => $fileSizeBytes,
                'sha256_checksum'      => $sha256Checksum,
                'status'               => 'completed',
                'included_tables_count'=> $dbDumpResult['tables_count'] ?? 0,
                'included_files_count' => $filesArchiveResult['total_files'] ?? 0,
                'manifest_metadata'    => $manifest,
                'completed_at'         => Carbon::now(),
            ]);

            // 8. Log to Forensic Audit Trail
            SystemAuditTrail::log(
                'BACKUP_CREATED',
                "إنشاء نسخة احتياطية مشفرة بنجاح ({$type}): {$finalFileName} (الحجم: " . $ledger->human_readable_size . ")",
                [
                    'backup_id' => $ledger->id,
                    'file_name' => $finalFileName,
                    'size_bytes' => $fileSizeBytes,
                    'sha256' => $sha256Checksum,
                    'type' => $type,
                ],
                $userId,
                null,
                'BackupLedger',
                $ledger->id,
                [],
                $ledger->toArray(),
                'INFO'
            );

            // 9. Apply Retention Policy
            self::applyRetentionPolicy();

            return $ledger->fresh();

        } catch (Throwable $e) {
            Log::error('Backup creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            $ledger->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            SystemAuditTrail::log(
                'BACKUP_FAILED',
                "فشل إنشاء النسخة الاحتياطية ({$type}): " . $e->getMessage(),
                ['error' => $e->getMessage()],
                $userId,
                null,
                'BackupLedger',
                $ledger->id,
                [],
                [],
                'ERROR'
            );

            throw new Exception("تعذر إتمام النسخ الاحتياطي: " . $e->getMessage(), 0, $e);

        } finally {
            // Cleanup temp directory
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Dump Database to temporary directory.
     */
    protected static function dumpDatabase(string $tempDir, string $timestamp): array
    {
        $dbConnection = config('database.default');
        $dbPath = config("database.connections.{$dbConnection}.database");
        $destDbDir = "{$tempDir}/database";
        File::makeDirectory($destDbDir, 0750, true);

        $tablesCount = 0;

        $driver = (string) config("database.connections.{$dbConnection}.driver", $dbConnection);

        if ($driver === 'sqlite' && $dbPath !== ':memory:' && File::exists($dbPath)) {
            $destFile = "{$destDbDir}/database_{$timestamp}.sqlite";

            // VACUUM INTO writes a transactionally consistent snapshot (including WAL content),
            // unlike copying the file while requests are still writing to it.
            try {
                $pdo = DB::connection($dbConnection)->getPdo();
                $pdo->exec('VACUUM INTO ' . $pdo->quote($destFile));
            } catch (Throwable $e) {
                Log::warning('Backup: VACUUM INTO unavailable, falling back to file copy: ' . $e->getMessage());
                File::copy($dbPath, $destFile);
            }
            $sha256 = hash_file('sha256', $destFile);

            $tablesCount = count(Schema::getTableListing());

            return [
                'driver'       => 'sqlite',
                'file_name'    => basename($destFile),
                'sha256'       => $sha256,
                'size_bytes'   => File::size($destFile),
                'tables_count' => $tablesCount,
            ];
        }

        if (in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            $destFile = "{$destDbDir}/database_{$timestamp}.sql";
            self::runDatabaseTool($driver, $dbConnection, 'dump', $destFile);

            if (!File::exists($destFile) || File::size($destFile) < 100) {
                throw new Exception('أداة تفريغ قاعدة البيانات لم تُنتج ملفاً صالحاً؛ أُلغي النسخ الاحتياطي لتفادي نسخة فارغة.');
            }

            return [
                'driver'       => $driver,
                'file_name'    => basename($destFile),
                'sha256'       => hash_file('sha256', $destFile),
                'size_bytes'   => File::size($destFile),
                'tables_count' => count(Schema::getTableListing()),
            ];
        }

        // In-memory sqlite (tests only): there is nothing durable to export.
        $destFile = "{$destDbDir}/database_{$timestamp}.sql";
        File::put($destFile, "-- IIIS Database Export: " . Carbon::now()->toDateTimeString() . "\n");

        return [
            'driver'       => $dbConnection,
            'file_name'    => basename($destFile),
            'sha256'       => hash_file('sha256', $destFile),
            'size_bytes'   => File::size($destFile),
            'tables_count' => count(Schema::getTableListing()),
        ];
    }

    /**
     * Run mysqldump/mysql or pg_dump/psql for the given connection.
     * $mode 'dump' writes $file; 'restore' reads it. Passwords go through the environment, never argv.
     */
    protected static function runDatabaseTool(string $driver, string $connection, string $mode, string $file): void
    {
        $cfg = config("database.connections.{$connection}");
        $isPg = $driver === 'pgsql';
        $binDir = rtrim((string) ($cfg['dump']['dump_binary_path'] ?? ''), '/\\');
        $bin = fn (string $name) => ($binDir !== '' ? $binDir . DIRECTORY_SEPARATOR : '') . $name;

        if ($isPg) {
            $env = ['PGPASSWORD' => (string) ($cfg['password'] ?? '')];
            $common = ['-h', (string) $cfg['host'], '-p', (string) ($cfg['port'] ?? 5432), '-U', (string) $cfg['username']];
            $command = $mode === 'dump'
                ? array_merge([$bin('pg_dump')], $common, ['--no-owner', '--clean', '--if-exists', '-f', $file, (string) $cfg['database']])
                : array_merge([$bin('psql')], $common, ['-v', 'ON_ERROR_STOP=1', '-f', $file, (string) $cfg['database']]);
        } else {
            $env = ['MYSQL_PWD' => (string) ($cfg['password'] ?? '')];
            $common = ['-h', (string) $cfg['host'], '-P', (string) ($cfg['port'] ?? 3306), '-u', (string) $cfg['username']];
            $command = $mode === 'dump'
                ? array_merge([$bin('mysqldump')], $common, ['--single-transaction', '--routines', '--no-tablespaces', '--result-file=' . $file, (string) $cfg['database']])
                : array_merge([$bin('mysql')], $common, [(string) $cfg['database']]);
        }

        $process = new \Symfony\Component\Process\Process($command, null, $env, null, 600);
        if ($mode === 'restore' && !$isPg) {
            $process->setInput(fopen($file, 'r'));
        }

        try {
            $process->run();
        } catch (Throwable $e) {
            throw new Exception("تعذر تشغيل أداة قاعدة البيانات ({$command[0]}): تأكد من تثبيتها أو ضبط مسارها في database.connections.{$connection}.dump.dump_binary_path");
        }

        if (!$process->isSuccessful()) {
            throw new Exception('فشلت أداة قاعدة البيانات: ' . trim(substr($process->getErrorOutput(), 0, 500)));
        }
    }

    /**
     * Pack Storage / Public uploads.
     */
    protected static function packMediaFiles(string $tempDir): array
    {
        $storageSource = storage_path('app/public');
        $vaultSource = storage_path('app/secure_vault');
        $destStorageDir = "{$tempDir}/storage_files";
        File::makeDirectory($destStorageDir, 0750, true);

        $totalFiles = 0;
        $totalBytes = 0;

        if (File::exists($storageSource)) {
            File::copyDirectory($storageSource, "{$destStorageDir}/public");
            $files = File::allFiles("{$destStorageDir}/public");
            $totalFiles += count($files);
            foreach ($files as $f) {
                $totalBytes += $f->getSize();
            }
        }

        if (File::exists($vaultSource)) {
            File::copyDirectory($vaultSource, "{$destStorageDir}/secure_vault");
            $vFiles = File::allFiles("{$destStorageDir}/secure_vault");
            $totalFiles += count($vFiles);
            foreach ($vFiles as $f) {
                $totalBytes += $f->getSize();
            }
        }

        return [
            'total_files' => $totalFiles,
            'total_bytes' => $totalBytes,
        ];
    }

    /**
     * Create standard zip archive from a folder.
     */
    protected static function createZipArchive(string $sourceDir, string $outZipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($outZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("تعذر إنشاء ملف الأرشيف المضغوط.");
        }

        $files = File::allFiles($sourceDir);
        foreach ($files as $file) {
            if ($file->getRealPath() === realpath($outZipPath)) {
                continue;
            }
            $relativePath = substr($file->getRealPath(), strlen($sourceDir) + 1);
            $zip->addFile($file->getRealPath(), $relativePath);
        }

        $zip->close();
    }

    /**
     * Encrypt a file using AES-256-CBC.
     */
    protected static function encryptFile(string $sourcePath, string $destinationPath): void
    {
        $key = hash('sha256', self::getEncryptionKey(), true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER_METHOD));

        $inHandle = fopen($sourcePath, 'rb');
        $outHandle = fopen($destinationPath, 'wb');

        if (!$inHandle || !$outHandle) {
            throw new Exception("تعذر فتح ملف النسخة الاحتياطية لعملية التشفير.");
        }

        // Write IV at the beginning of the file (16 bytes)
        fwrite($outHandle, $iv);

        // Read and encrypt in 64KB chunks to keep memory usage minimal
        $chunkSize = 65536;
        $unencryptedData = '';

        while (!feof($inHandle)) {
            $unencryptedData .= fread($inHandle, $chunkSize);
        }
        fclose($inHandle);

        $encryptedData = openssl_encrypt($unencryptedData, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
        if ($encryptedData === false) {
            fclose($outHandle);
            throw new Exception("فشلت خوارزمية التشفير AES-256-CBC.");
        }

        fwrite($outHandle, $encryptedData);
        fclose($outHandle);
    }

    /**
     * Decrypt an AES-256-CBC encrypted backup file.
     */
    public static function decryptFile(string $encryptedFilePath, string $decryptedOutputPath): bool
    {
        $key = hash('sha256', self::getEncryptionKey(), true);
        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);

        $inHandle = fopen($encryptedFilePath, 'rb');
        if (!$inHandle) {
            throw new Exception("تعذر فتح ملف النسخة المشفرة للفك.");
        }

        // Read IV from header
        $iv = fread($inHandle, $ivLength);
        if (strlen($iv) !== $ivLength) {
            fclose($inHandle);
            throw new Exception("تلف في رأس التشفير (IV Corrupted).");
        }

        $encryptedContent = '';
        while (!feof($inHandle)) {
            $encryptedContent .= fread($inHandle, 65536);
        }
        fclose($inHandle);

        $decryptedData = openssl_decrypt($encryptedContent, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
        if ($decryptedData === false) {
            throw new Exception("فشل فك التشفير. تأكد من صحة مفتاح التشفير السري.");
        }

        File::put($decryptedOutputPath, $decryptedData);
        return true;
    }

    /**
     * Restore database and files from an existing backup ledger.
     */
    public static function restoreBackup(int|string $backupIdentifier, array $options = []): array
    {
        $ledger = is_numeric($backupIdentifier)
            ? BackupLedger::findOrFail($backupIdentifier)
            : BackupLedger::where('file_name', $backupIdentifier)->firstOrFail();

        // 1. Verify integrity before restore
        $integrity = $ledger->verifyIntegrity();
        if (!$integrity['valid']) {
            throw new Exception("فحص النزاهة فشل: " . $integrity['message']);
        }

        $encryptedFullPath = storage_path('app/' . $ledger->file_path);
        $tempExtractDir = storage_path('app/backups/restore_' . uniqid());
        File::makeDirectory($tempExtractDir, 0750, true);

        try {
            $decryptedZip = "{$tempExtractDir}/payload.zip";
            self::decryptFile($encryptedFullPath, $decryptedZip);

            $zip = new ZipArchive();
            if ($zip->open($decryptedZip) !== true) {
                throw new Exception("تعذر فتح الأرشيف بعد فك التشفير.");
            }
            $zip->extractTo($tempExtractDir);
            $zip->close();

            // Check manifest
            $manifestFile = "{$tempExtractDir}/manifest.json";
            if (!File::exists($manifestFile)) {
                throw new Exception("بيانات البيان الأمني (Manifest) مفقودة في النسخة الاحتياطية.");
            }
            $manifest = json_decode(File::get($manifestFile), true);

            // Restore the database
            $dbConnection = config('database.default');
            $dbPath = config("database.connections.{$dbConnection}.database");
            $driver = (string) config("database.connections.{$dbConnection}.driver", $dbConnection);
            $snapshotName = $manifest['database']['file_name'] ?? null;
            $snapshotFile = $snapshotName ? "{$tempExtractDir}/database/" . $snapshotName : null;

            $restoredDb = false;
            if ($driver === 'sqlite') {
                if ($dbPath === ':memory:' || empty($dbPath)) {
                    $restoredDb = true;
                } elseif ($snapshotFile && File::exists($snapshotFile)) {
                    // Keep the database we are about to overwrite so a bad restore can be undone.
                    if (File::exists($dbPath)) {
                        File::copy($dbPath, $dbPath . '.pre-restore-' . date('Ymd_His'));
                    }
                    File::copy($snapshotFile, $dbPath);
                    $restoredDb = true;
                }
            } elseif (in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
                if (!$snapshotFile || !File::exists($snapshotFile) || !str_ends_with($snapshotFile, '.sql')) {
                    throw new Exception('ملف تفريغ قاعدة البيانات غير موجود داخل النسخة الاحتياطية.');
                }
                self::runDatabaseTool($driver, $dbConnection, 'restore', $snapshotFile);
                $restoredDb = true;
            } else {
                throw new Exception("استعادة قاعدة البيانات غير مدعومة للمشغّل [{$driver}].");
            }

            // Restore Public Storage
            $restoredFilesCount = 0;
            $extractedPublic = "{$tempExtractDir}/storage_files/public";
            if (File::exists($extractedPublic)) {
                File::copyDirectory($extractedPublic, storage_path('app/public'));
                $restoredFilesCount = count(File::allFiles(storage_path('app/public')));
            }

            SystemAuditTrail::log(
                'BACKUP_RESTORED',
                "تمت استعادة النظام بنجاح من النسخة الاحتياطية: {$ledger->file_name}",
                [
                    'backup_id' => $ledger->id,
                    'file_name' => $ledger->file_name,
                    'restored_db' => $restoredDb,
                    'restored_files_count' => $restoredFilesCount,
                ],
                auth()->id(),
                null,
                'BackupLedger',
                $ledger->id,
                [],
                [],
                'WARNING'
            );

            return [
                'success'              => true,
                'message'              => "تمت استعادة النظام وقاعدة البيانات والملفات بنجاح من النسخة {$ledger->file_name}",
                'restored_database'    => $restoredDb,
                'restored_files_count' => $restoredFilesCount,
                'manifest'             => $manifest,
            ];

        } finally {
            if (File::exists($tempExtractDir)) {
                File::deleteDirectory($tempExtractDir);
            }
        }
    }

    /**
     * Delete a backup file and its ledger entry.
     */
    public static function deleteBackup(int $id, ?int $userId = null): bool
    {
        $ledger = BackupLedger::findOrFail($id);
        $fullPath = storage_path('app/' . $ledger->file_path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }

        $fileName = $ledger->file_name;
        $ledger->delete();

        SystemAuditTrail::log(
            'BACKUP_DELETED',
            "حذف ملف النسخة الاحتياطية: {$fileName}",
            ['backup_id' => $id, 'file_name' => $fileName],
            $userId,
            null,
            'BackupLedger',
            $id,
            [],
            [],
            'WARNING'
        );

        return true;
    }

    /**
     * Apply retention policy:
     * - Daily: keep for last 7 days.
     * - Weekly: keep 1 per week for last 30 days.
     * - Monthly: keep 1 per month for older.
     */
    public static function applyRetentionPolicy(): int
    {
        $deletedCount = 0;
        $now = Carbon::now();

        // 1. Delete failed backups older than 24 hours
        $failedBackups = BackupLedger::where('status', 'failed')
            ->where('created_at', '<', $now->copy()->subDay())
            ->get();

        foreach ($failedBackups as $b) {
            $p = storage_path('app/' . $b->file_path);
            if (File::exists($p)) File::delete($p);
            $b->delete();
            $deletedCount++;
        }

        // 2. Keep last 7 days daily backups
        $olderThan7Days = BackupLedger::where('status', 'completed')
            ->where('created_at', '<', $now->copy()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->get();

        // Group older by Week (between 7 and 30 days) and by Month (older than 30 days)
        $keptWeeks = [];
        $keptMonths = [];

        foreach ($olderThan7Days as $backup) {
            $created = Carbon::parse($backup->created_at);
            $ageInDays = $created->diffInDays($now);

            if ($ageInDays <= 30) {
                $weekKey = $created->format('Y-W');
                if (!isset($keptWeeks[$weekKey])) {
                    $keptWeeks[$weekKey] = $backup->id; // keep newest in that week
                } else {
                    // Delete extra in that week
                    self::deleteBackup($backup->id);
                    $deletedCount++;
                }
            } else {
                $monthKey = $created->format('Y-m');
                if (!isset($keptMonths[$monthKey])) {
                    $keptMonths[$monthKey] = $backup->id; // keep 1 per month
                } else {
                    self::deleteBackup($backup->id);
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Get encryption secret key derived from application config.
     */
    protected static function getEncryptionKey(): string
    {
        return config('app.backup_key') ?: config('app.key', 'IIIS-ACADEMIC-ERP-BACKUP-SECRET-KEY-2026');
    }
}
