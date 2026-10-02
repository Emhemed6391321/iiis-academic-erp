<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteBackupJob;
use App\Models\BackupLedger;
use App\Models\SystemAuditTrail;
use App\Services\EnterpriseBackupService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    /**
     * Check if authenticated user is Super Admin or Global Administrator.
     */
    protected function authorizeSuperAdmin(): void
    {
        $user = Auth::user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || ($user->role && $user->role->is_global));

        if (!$isSuperAdmin) {
            abort(403, 'غير مصرح: إدارة النسخ الاحتياطي واستعادة النظام مقتصرة حصرياً على مدير النظام العام (Super Admin).');
        }
    }

    /**
     * List all backups with storage statistics and health overview.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $perPage = min(100, max(10, (int)$request->query('per_page', 20)));
        $type = $request->query('type');
        $status = $request->query('status');

        $query = BackupLedger::with('initiatedBy:id,name,email')
            ->orderByDesc('id');

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $backups = $query->paginate($perPage);

        // Calculate summary metrics
        $totalBytes = BackupLedger::where('status', 'completed')->sum('file_size_bytes');
        $totalCompleted = BackupLedger::where('status', 'completed')->count();
        $totalFailed = BackupLedger::where('status', 'failed')->count();
        $lastBackup = BackupLedger::where('status', 'completed')->orderByDesc('id')->first();

        return response()->json([
            'status' => 'success',
            'data'   => $backups->items(),
            'pagination' => [
                'current_page' => $backups->currentPage(),
                'last_page'    => $backups->lastPage(),
                'per_page'     => $backups->perPage(),
                'total'        => $backups->total(),
            ],
            'summary' => [
                'total_completed_backups' => $totalCompleted,
                'total_failed_backups'    => $totalFailed,
                'total_storage_bytes'     => $totalBytes,
                'total_storage_human'     => $this->formatBytes($totalBytes),
                'last_backup_at'          => $lastBackup ? $lastBackup->created_at->toDateTimeString() : null,
                'last_backup_age_hours'   => $lastBackup ? Carbon::parse($lastBackup->created_at)->diffInHours(now()) : null,
                'encryption_standard'     => 'AES-256-CBC (FIPS Compliant)',
            ],
        ]);
    }

    /**
     * Trigger manual backup (async or sync).
     */
    public function createManualBackup(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $runSync = $request->boolean('sync', false);
        $user = Auth::user();

        if ($runSync) {
            try {
                $ledger = EnterpriseBackupService::createBackup('manual', $user?->id);
                return response()->json([
                    'status'  => 'success',
                    'message' => 'تم إنشاء النسخة الاحتياطية المشفرة وتوثيقها في السجل بنجاح.',
                    'data'    => $ledger,
                ], 201);
            } catch (Throwable $e) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'فشلت عملية النسخ الاحتياطي: ' . $e->getMessage(),
                ], 500);
            }
        }

        // Dispatch asynchronous queue job
        ExecuteBackupJob::dispatch('manual', $user?->id);

        SystemAuditTrail::log(
            'BACKUP_DISPATCHED',
            'تم إطلاق عملية نسخ احتياطي يدوي في الخلفية بواسطة: ' . ($user?->name ?? 'مدير النظام'),
            ['type' => 'manual', 'user_id' => $user?->id],
            $user?->id,
            null,
            'BackupLedger',
            null,
            [],
            [],
            'INFO'
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم بدء عملية النسخ الاحتياطي في الخلفية بنجاح. ستظهر النسخة في السجل فور اكتمال التشفير.',
        ], 202);
    }

    /**
     * Download an encrypted backup file securely.
     */
    public function downloadBackup(Request $request, int $id): BinaryFileResponse|JsonResponse
    {
        $this->authorizeSuperAdmin();

        $ledger = BackupLedger::findOrFail($id);
        $fullPath = storage_path('app/' . $ledger->file_path);

        if (!File::exists($fullPath)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'ملف النسخة الاحتياطية غير موجود على الخادم.',
            ], 404);
        }

        // Verify SHA-256 integrity before download
        $integrity = $ledger->verifyIntegrity();
        if (!$integrity['valid']) {
            return response()->json([
                'status'  => 'error',
                'message' => 'تحذير أمني: البصمة الرقمية للملف غير مطابقة للسجل.',
                'details' => $integrity,
            ], 422);
        }

        $user = Auth::user();
        SystemAuditTrail::log(
            'BACKUP_DOWNLOADED',
            "تحميل ملف النسخة الاحتياطية المشفرة: {$ledger->file_name} بواسطة {$user?->name}",
            [
                'backup_id' => $ledger->id,
                'file_name' => $ledger->file_name,
                'sha256'    => $ledger->sha256_checksum,
                'ip'        => $request->ip(),
                'user_agent'=> $request->userAgent(),
            ],
            $user?->id,
            null,
            'BackupLedger',
            $ledger->id,
            [],
            [],
            'INFO'
        );

        return response()->download($fullPath, $ledger->file_name, [
            'Content-Type'              => 'application/octet-stream',
            'X-SHA256-Checksum'         => $ledger->sha256_checksum,
            'X-Encryption-Algorithm'    => $ledger->encryption_algorithm,
            'Content-Disposition'       => "attachment; filename=\"{$ledger->file_name}\"",
        ]);
    }

    /**
     * Verify on-demand integrity of a backup file.
     */
    public function verifyIntegrity(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $ledger = BackupLedger::findOrFail($id);
        $result = $ledger->verifyIntegrity();

        return response()->json([
            'status' => $result['valid'] ? 'success' : 'error',
            'data'   => $result,
        ]);
    }

    /**
     * Restore from a backup.
     */
    public function restoreBackup(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin();

        try {
            $result = EnterpriseBackupService::restoreBackup($id);
            return response()->json([
                'status'  => 'success',
                'message' => $result['message'],
                'data'    => $result,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'تعذر استعادة النسخة الاحتياطية: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a backup.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeSuperAdmin();

        try {
            EnterpriseBackupService::deleteBackup($id, Auth::id());
            return response()->json([
                'status'  => 'success',
                'message' => 'تم حذف ملف النسخة الاحتياطية وسجلها بنجاح.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'تعذر حذف النسخة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper to format bytes.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
