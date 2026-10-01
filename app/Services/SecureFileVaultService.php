<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SecureFileVaultService
{
    protected AntivirusScanService $antivirus;

    public function __construct(AntivirusScanService $antivirus)
    {
        $this->antivirus = $antivirus;
    }

    /**
     * Store and secure an uploaded file or raw binary in the encrypted private vault.
     * 
     * @param UploadedFile|string $file UploadedFile instance or raw base64 string
     * @param string $category Subdirectory (e.g. 'students/photos', 'students/documents')
     * @param int|null $branchId Branch ID for access control segregation
     * @return array{success: bool, path: string|null, mime: string|null, hash: string|null, error: string|null}
     */
    public function storeSecure(mixed $file, string $category, ?int $branchId = null): array
    {
        $binary = null;
        $originalName = 'document';

        if ($file instanceof UploadedFile) {
            $binary = file_get_contents($file->getRealPath());
            $originalName = $file->getClientOriginalName();
        } elseif (is_string($file)) {
            // Process base64
            if (str_contains($file, ';base64,')) {
                $file = substr($file, strpos($file, ';base64,') + 8);
            }
            $binary = base64_decode($file, true);
        }

        if (!$binary || strlen($binary) === 0) {
            return ['success' => false, 'path' => null, 'mime' => null, 'hash' => null, 'error' => 'الملف فارغ أو تعذر قراءته.'];
        }

        // 1. Max size check (15 MB)
        if (strlen($binary) > 15 * 1024 * 1024) {
            return ['success' => false, 'path' => null, 'mime' => null, 'hash' => null, 'error' => 'حجم الملف يتجاوز الحد الأقصى المسموح به (15 ميغابايت).'];
        }

        // 2. Validate Magic Bytes (True MIME detection)
        $detectedMime = $this->detectMagicMime($binary);
        if (!$detectedMime) {
            return ['success' => false, 'path' => null, 'mime' => null, 'hash' => null, 'error' => 'نوع الملف غير مسموح به. يُسمح فقط بملفات PDF والصور (JPG, PNG, WebP).'];
        }

        // 3. Antivirus & Heuristic Scan
        $scan = $this->antivirus->scanBuffer($binary, $originalName);
        if (!$scan['is_clean']) {
            return ['success' => false, 'path' => null, 'mime' => null, 'hash' => null, 'error' => "تم حظر الملف: تم اكتشاف برمجية مشبوهة ({$scan['threat']})."];
        }

        // 4. Strip EXIF Metadata from JPEG/PNG images
        $cleanBinary = $this->stripExifMetadata($binary, $detectedMime);

        // 5. Encrypt at rest
        $encryptedContent = Crypt::encrypt($cleanBinary, false);

        // 6. Store in private storage outside public
        $uuid = (string) Str::uuid();
        $extension = $this->extensionForMime($detectedMime);
        $branchDir = $branchId ? "branch_{$branchId}" : 'hq';
        $vaultPath = "secure_vault/{$category}/{$branchDir}/{$uuid}.{$extension}.enc";

        Storage::disk('local')->put($vaultPath, $encryptedContent);

        $hash = hash('sha256', $cleanBinary);

        return [
            'success' => true,
            'path' => $vaultPath,
            'mime' => $detectedMime,
            'hash' => $hash,
            'error' => null,
        ];
    }

    /**
     * Retrieve and decrypt a file from the secure vault.
     */
    public function retrieveDecrypted(string $vaultPath): ?string
    {
        if (!Storage::disk('local')->exists($vaultPath)) {
            return null;
        }

        $encrypted = Storage::disk('local')->get($vaultPath);
        if (!$encrypted) {
            return null;
        }

        try {
            return Crypt::decrypt($encrypted, false);
        } catch (\Throwable $e) {
            // Fallback for unencrypted legacy files during migration
            return $encrypted;
        }
    }

    /**
     * Generate a temporary signed download URL for authorized access.
     */
    public function generateSignedUrl(string $vaultPath, int $minutes = 30): string
    {
        return URL::temporarySignedRoute(
            'secure.vault.download',
            now()->addMinutes($minutes),
            ['path' => base64_encode($vaultPath)]
        );
    }

    /**
     * Check magic bytes strictly against binary signatures.
     */
    public function detectMagicMime(string $binary): ?string
    {
        // PDF Magic Bytes: %PDF- (\x25\x50\x44\x46\x2D)
        if (str_starts_with($binary, "%PDF-")) {
            return 'application/pdf';
        }

        // PNG Magic Bytes: \x89PNG\r\n\x1a\n
        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }

        // JPEG Magic Bytes: \xFF\xD8\xFF
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        // WebP Magic Bytes: RIFF....WEBP
        if (str_starts_with($binary, 'RIFF') && substr($binary, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        return null;
    }

    /**
     * Strip EXIF and camera metadata to protect student privacy and remove metadata payloads.
     */
    private function stripExifMetadata(string $binary, string $mime): string
    {
        if (!str_starts_with($mime, 'image/') || $mime === 'image/webp') {
            return $binary;
        }

        if (function_exists('imagecreatefromstring')) {
            try {
                $image = @imagecreatefromstring($binary);
                if ($image) {
                    ob_start();
                    if ($mime === 'image/png') {
                        imagepng($image, null, 8);
                    } else {
                        imagejpeg($image, null, 90);
                    }
                    $cleaned = ob_get_clean();
                    imagedestroy($image);

                    if ($cleaned && strlen($cleaned) > 0) {
                        return $cleaned;
                    }
                }
            } catch (\Throwable $e) {
                // Return original if GD fails
            }
        }

        return $binary;
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
