<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SecureFileVaultService;
use App\Models\SystemAuditTrail;
use Illuminate\Support\Facades\Auth;

class SecureVaultDownloadController extends Controller
{
    protected SecureFileVaultService $vault;

    public function __construct(SecureFileVaultService $vault)
    {
        $this->vault = $vault;
    }

    /**
     * Download or view a secured and encrypted file from the vault.
     */
    public function download(Request $request)
    {
        if (!$request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'رابط التحميل غير صالح أو منتهي الصلاحية.',
            ], 403);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'يرجى تسجيل الدخول أولاً.'], 401);
        }

        $encodedPath = $request->query('path');
        $vaultPath = base64_decode($encodedPath);

        if (!$vaultPath || !str_starts_with($vaultPath, 'secure_vault/')
            || str_contains($vaultPath, '..') || str_contains($vaultPath, '\\') || str_contains($vaultPath, "\0")) {
            return response()->json(['success' => false, 'message' => 'مسار الملف غير مصرح به.'], 400);
        }

        // Branch Isolation Check on File Path
        if (!$user->hasGlobalAccessScope()) {
            if (preg_match('/branch_(\d+)/', $vaultPath, $matches)) {
                $targetBranchId = (int) $matches[1];
                if ((int)$user->branch_id !== $targetBranchId) {
                    SystemAuditTrail::log(
                        'UNAUTHORIZED_FILE_ACCESS_ATTEMPT',
                        "محاولة غير مصرح بها للوصول إلى ملف مشفر يتبع الفرع [{$targetBranchId}] بواسطة المستخدم [{$user->name}].",
                        ['user_id' => $user->id, 'file_path' => $vaultPath, 'ip' => $request->ip()]
                    );

                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح: الملف يتبع فرعاً تعليمياً آخر.',
                    ], 403);
                }
            }
        }

        $content = $this->vault->retrieveDecrypted($vaultPath);
        if (!$content) {
            return response()->json(['success' => false, 'message' => 'الملف المطلوب غير موجود في الخزنة الرقمية.'], 404);
        }

        // Determine MIME
        $mime = $this->vault->detectMagicMime($content) ?? 'application/octet-stream';

        // Audit sensitive document read
        SystemAuditTrail::log(
            'SECURE_FILE_ACCESSED',
            "تمت قراءة وتحميل وثيقة من الخزنة الرقمية: [{$vaultPath}] بواسطة [{$user->name}].",
            ['user_id' => $user->id, 'file_path' => $vaultPath, 'ip' => $request->ip()]
        );

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($vaultPath, '.enc') . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }
}
