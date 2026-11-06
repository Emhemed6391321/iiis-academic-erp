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
            $filesArchiveResult = self::packMediaFiles($tempDir, (bool) ($options['include_vault_files'] ?? false));

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
    protected static function packMediaFiles(string $tempDir, bool $includeVaultFiles = false): array
    {
        $storageSource = storage_path('app/public');
        $vaultSource = \App\Support\SecureVaultPaths::primary();
        $legacyVaultSource = \App\Support\SecureVaultPaths::legacy();
        $destStorageDir = "{$tempDir}/storage_files";
        File::makeDirectory($destStorageDir, 0750, true);

        $totalFiles = 0;
        $totalBytes = 0;
        $vaultFiles = 0;
        $legacyFiles = 0;

        if (File::exists($storageSource)) {
            File::copyDirectory($storageSource, "{$destStorageDir}/public");
            $files = File::allFiles("{$destStorageDir}/public");
            $totalFiles += count($files);
            foreach ($files as $f) {
                $totalBytes += $f->getSize();
            }
        }

        // Incremental mode (default): vault files are immutable, so they are mirrored
        // once and the archive only carries a manifest (path + size + sha256).
        $mirror = self::syncVaultMirror();
        File::put("{$tempDir}/vault_manifest.json", json_encode([
            'format'  => 1,
            'mirror'  => 'storage/app/backups/vault_mirror',
            'files'   => $mirror['files'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $mirrorSummary = [
            'manifest_files' => count($mirror['files']),
            'copied_files'   => $mirror['copied'],
            'copied_bytes'   => $mirror['copied_bytes'],
        ];

        if (!$includeVaultFiles) {
            return [
                'mode' => 'incremental',
                'vault_files' => count($mirror['files']),
                'legacy_vault_files' => 0,
                'vault_mirror' => $mirrorSummary,
                'total_files' => $totalFiles,
                'total_bytes' => $totalBytes,
            ];
        }

        if (File::exists($vaultSource)) {
            File::copyDirectory($vaultSource, "{$destStorageDir}/secure_vault");
            $vFiles = File::allFiles("{$destStorageDir}/secure_vault");
            $vaultFiles = count($vFiles);
            $totalFiles += $vaultFiles;
            foreach ($vFiles as $f) {
                $totalBytes += $f->getSize();
            }
        }

        // Files written before the local disk root moved to storage/app/private
        if (File::exists($legacyVaultSource) && File::allFiles($legacyVaultSource)) {
            File::copyDirectory($legacyVaultSource, "{$destStorageDir}/secure_vault_legacy");
            $lFiles = File::allFiles("{$destStorageDir}/secure_vault_legacy");
            $legacyFiles = count($lFiles);
            $totalFiles += $legacyFiles;
            foreach ($lFiles as $f) {
                $totalBytes += $f->getSize();
            }
        }

        return [
            'mode' => 'full',
            'vault_files' => $vaultFiles,
            'legacy_vault_files' => $legacyFiles,
            'vault_mirror' => $mirrorSummary,
            'total_files' => $totalFiles,
            'total_bytes' => $totalBytes,
        ];
    }

    /**
     * Copy new/changed vault files into the write-only mirror and build the manifest.
     * Never deletes anything from the mirror.
     *
     * @return array{files: array<int,array{root:string,path:string,size:int,sha256:string}>, copied:int, copied_bytes:int}
     */
    protected static function syncVaultMirror(): array
    {
        $mirrorRoot = \App\Support\SecureVaultPaths::mirror();
        $files = [];
        $copied = 0;
        $copiedBytes = 0;

        foreach (\App\Support\SecureVaultPaths::roots() as $rootKey => $sourceRoot) {
            if (!File::isDirectory($sourceRoot)) {
                continue;
            }
            foreach (File::allFiles($sourceRoot) as $file) {
                $relative = str_replace('\\', '/', $file->getRelativePathname());
                if (str_ends_with($relative, '.health_check')) {
                    continue;
                }
                $source = $file->getRealPath();
                $size = $file->getSize();
                $sha = hash_file('sha256', $source);
                $target = "{$mirrorRoot}/{$rootKey}/{$relative}";

                $needsCopy = !File::exists($target)
                    || File::size($target) !== $size
                    || hash_file('sha256', $target) !== $sha;

                if ($needsCopy) {
                    File::ensureDirectoryExists(dirname($target), 0750);
                    $tmp = $target . '.part';
                    if (!@copy($source, $tmp) || hash_file('sha256', $tmp) !== $sha) {
                        @unlink($tmp);
                        throw new Exception("تعذر نسخ ملف الخزنة إلى المرآة: {$relative}");
                    }
                    if (File::exists($target)) {
                        // Keep the previous copy instead of overwriting it.
                        @rename($target, $target . '.prev_' . date('Ymd_His'));
                    }
                    rename($tmp, $target);
                    $copied++;
                    $copiedBytes += $size;
                }

                $files[] = ['root' => $rootKey, 'path' => $relative, 'size' => $size, 'sha256' => $sha];
            }
        }

        return ['files' => $files, 'copied' => $copied, 'copied_bytes' => $copiedBytes];
    }

    /**
     * Rebuild vault files listed in a backup manifest from the mirror, verifying sha256.
     * Merge only: existing files are left untouched when already identical.
     *
     * @return array{restored:int, missing:int, missing_paths: string[]}
     */
    protected static function restoreVaultFromMirror(array $manifestFiles): array
    {
        $mirrorRoot = \App\Support\SecureVaultPaths::mirror();
        $roots = \App\Support\SecureVaultPaths::roots();
        $restored = 0;
        $missing = [];

        foreach ($manifestFiles as $entry) {
            $rootKey = $entry['root'] ?? '';
            $relative = (string) ($entry['path'] ?? '');
            if (!isset($roots[$rootKey]) || $relative === '' || str_contains($relative, '..')) {
                $missing[] = "{$rootKey}/{$relative}";
                continue;
            }

            $target = "{$roots[$rootKey]}/{$relative}";
            if (File::exists($target) && hash_file('sha256', $target) === $entry['sha256']) {
                continue;
            }

            $source = "{$mirrorRoot}/{$rootKey}/{$relative}";
            if (!File::exists($source) || hash_file('sha256', $source) !== $entry['sha256']) {
                $missing[] = "{$rootKey}/{$relative}";
                continue;
            }

            File::ensureDirectoryExists(dirname($target), 0750);
            File::copy($source, $target);
            $restored++;
        }

        return ['restored' => $restored, 'missing' => count($missing), 'missing_paths' => array_slice($missing, 0, 50)];
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

        // Stream in 1MB chunks (multiple of the 16-byte AES block). Full chunks are
        // encrypted without padding and chained via the last ciphertext block; only the
        // final chunk gets PKCS#7 padding. The output is byte-identical to a one-shot
        // openssl_encrypt() of the whole file, so older backups stay compatible.
        $chunkSize = self::STREAM_CHUNK_BYTES;
        $chainIv = $iv;
        $current = self::readExactly($inHandle, $chunkSize);

        try {
            while (true) {
                $next = self::readExactly($inHandle, $chunkSize);
                $isLast = ($next === '');
                $options = OPENSSL_RAW_DATA | ($isLast ? 0 : OPENSSL_ZERO_PADDING);

                $cipher = openssl_encrypt($current, self::CIPHER_METHOD, $key, $options, $chainIv);
                if ($cipher === false) {
                    throw new Exception("فشلت خوارزمية التشفير AES-256-CBC.");
                }
                fwrite($outHandle, $cipher);

                if ($isLast) {
                    break;
                }
                $chainIv = substr($cipher, -16);
                $current = $next;
            }
        } finally {
            fclose($inHandle);
            fclose($outHandle);
        }
    }

    /** Streaming chunk size; must be a multiple of the 16-byte AES block. */
    private const STREAM_CHUNK_BYTES = 1048576;

    /** Read up to $length bytes, looping over short reads; '' only at EOF. */
    private static function readExactly($handle, int $length): string
    {
        $buffer = '';
        while (strlen($buffer) < $length && !feof($handle)) {
            $part = fread($handle, $length - strlen($buffer));
            if ($part === false || $part === '') {
                break;
            }
            $buffer .= $part;
        }
        return $buffer;
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

        $outHandle = fopen($decryptedOutputPath, 'wb');
        if (!$outHandle) {
            fclose($inHandle);
            throw new Exception("تعذر إنشاء ملف الإخراج لفك التشفير.");
        }

        // Stream-decrypt: mirror of encryptFile(). Padding is only removed on the final chunk.
        $chainIv = $iv;
        $failed = false;
        try {
            $current = self::readExactly($inHandle, self::STREAM_CHUNK_BYTES);
            while (true) {
                $next = self::readExactly($inHandle, self::STREAM_CHUNK_BYTES);
                $isLast = ($next === '');

                if ($current === '' || strlen($current) % 16 !== 0) {
                    $failed = true;
                    break;
                }

                $options = OPENSSL_RAW_DATA | ($isLast ? 0 : OPENSSL_ZERO_PADDING);
                $plain = openssl_decrypt($current, self::CIPHER_METHOD, $key, $options, $chainIv);
                if ($plain === false) {
                    $failed = true;
                    break;
                }
                fwrite($outHandle, $plain);

                if ($isLast) {
                    break;
                }
                $chainIv = substr($current, -16);
                $current = $next;
            }
        } finally {
            fclose($inHandle);
            fclose($outHandle);
        }

        if ($failed) {
            @unlink($decryptedOutputPath);
            throw new Exception("فشل فك التشفير. تأكد من صحة مفتاح التشفير السري.");
        }

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

            // Restore encrypted vault (merge: copies over, never deletes existing files)
            $restoredVaultFiles = 0;
            $extractedVault = "{$tempExtractDir}/storage_files/secure_vault";
            if (File::exists($extractedVault)) {
                File::ensureDirectoryExists(\App\Support\SecureVaultPaths::primary(), 0750);
                File::copyDirectory($extractedVault, \App\Support\SecureVaultPaths::primary());
                $restoredVaultFiles += count(File::allFiles($extractedVault));
            }
            $extractedLegacyVault = "{$tempExtractDir}/storage_files/secure_vault_legacy";
            if (File::exists($extractedLegacyVault)) {
                File::ensureDirectoryExists(\App\Support\SecureVaultPaths::legacy(), 0750);
                File::copyDirectory($extractedLegacyVault, \App\Support\SecureVaultPaths::legacy());
                $restoredVaultFiles += count(File::allFiles($extractedLegacyVault));
            }

            // Incremental backups: rebuild the vault from the mirror using the manifest
            $missingVaultFiles = 0;
            $missingVaultPaths = [];
            $vaultManifestFile = "{$tempExtractDir}/vault_manifest.json";
            if (File::exists($vaultManifestFile) && !File::exists($extractedVault)) {
                $vaultManifest = json_decode(File::get($vaultManifestFile), true) ?: [];
                $mirrorResult = self::restoreVaultFromMirror($vaultManifest['files'] ?? []);
                $restoredVaultFiles += $mirrorResult['restored'];
                $missingVaultFiles = $mirrorResult['missing'];
                $missingVaultPaths = $mirrorResult['missing_paths'];
            }

            SystemAuditTrail::log(
                'BACKUP_RESTORED',
                "تمت استعادة النظام بنجاح من النسخة الاحتياطية: {$ledger->file_name}",
                [
                    'backup_id' => $ledger->id,
                    'file_name' => $ledger->file_name,
                    'restored_db' => $restoredDb,
                    'restored_files_count' => $restoredFilesCount,
                    'restored_vault_files' => $restoredVaultFiles,
                    'missing_vault_files' => $missingVaultFiles,
                    'missing_vault_paths' => $missingVaultPaths,
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
                'restored_vault_files' => $restoredVaultFiles,
                'missing_vault_files'  => $missingVaultFiles,
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
