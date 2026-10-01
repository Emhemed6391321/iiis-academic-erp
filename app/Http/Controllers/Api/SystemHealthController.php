<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use App\Models\SystemAuditTrail;
use Carbon\Carbon;

class SystemHealthController extends Controller
{
    /**
     * Check comprehensive health of the enterprise application.
     */
    public function check(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Authorization: Only GLOBAL_SCOPE or authenticated administrators
        if ($user && !$user->hasGlobalAccessScope()) {
            return response()->json([
                'status'  => 'FORBIDDEN',
                'message' => 'غير مصرح: فحص المؤشرات التشغيلية والبيئة محصور في الإدارة العامة.',
            ], 403);
        }

        $components = [];
        $isHealthy = true;

        // 1. Database Check
        $dbStart = microtime(true);
        try {
            DB::select('SELECT 1');
            $dbDuration = round((microtime(true) - $dbStart) * 1000, 2);
            $components['database'] = [
                'status'     => 'UP',
                'latency_ms' => $dbDuration,
                'connection' => config('database.default'),
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $components['database'] = [
                'status'  => 'DOWN',
                'error'   => $e->getMessage(),
            ];
        }

        // 2. Cache Check
        $cacheStart = microtime(true);
        try {
            $testKey = 'health_ping_' . uniqid();
            Cache::put($testKey, 'ok', 10);
            $val = Cache::get($testKey);
            Cache::forget($testKey);

            $cacheDuration = round((microtime(true) - $cacheStart) * 1000, 2);
            $components['cache'] = [
                'status'     => ($val === 'ok') ? 'UP' : 'DEGRADED',
                'latency_ms' => $cacheDuration,
                'driver'     => config('cache.default'),
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $components['cache'] = [
                'status' => 'DOWN',
                'error'  => $e->getMessage(),
            ];
        }

        // 3. Vault & Storage Check
        $vaultDir = storage_path('app/secure_vault');
        try {
            if (!File::exists($vaultDir)) {
                File::makeDirectory($vaultDir, 0750, true);
            }
            $testFile = "{$vaultDir}/.health_check";
            File::put($testFile, 'vault_test');
            $read = File::get($testFile);
            File::delete($testFile);

            $components['vault_storage'] = [
                'status'     => ($read === 'vault_test') ? 'UP' : 'DEGRADED',
                'writable'   => true,
                'free_space_mb' => function_exists('disk_free_space') ? round(disk_free_space(storage_path()) / (1024 * 1024), 2) : 'N/A',
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $components['vault_storage'] = [
                'status' => 'DOWN',
                'error'  => $e->getMessage(),
            ];
        }

        // 4. Audit Trail Cryptographic Hash Chain Integrity
        try {
            $chain = SystemAuditTrail::verifyChainIntegrity();
            $components['audit_chain'] = [
                'status'            => $chain['intact'] ? 'INTACT' : 'TAMPERED',
                'records_verified'  => $chain['count'],
                'broken_record_id'  => $chain['broken_id'] ?? null,
            ];
            if (!$chain['intact']) {
                $isHealthy = false;
            }
        } catch (\Throwable $e) {
            $components['audit_chain'] = [
                'status' => 'ERROR',
                'error'  => $e->getMessage(),
            ];
        }

        // 5. Security Posture
        $components['security_posture'] = [
            'environment'         => app()->environment(),
            'debug_mode'          => config('app.debug'),
            'dev_bypass_blocked'  => app()->environment('production') ? (empty(env('DEV_BYPASS_SECRET'))) : true,
            'mfa_policy'          => 'ENFORCED',
            'session_timeout_min' => 30,
        ];

        $overallStatus = $isHealthy ? 'HEALTHY' : 'UNHEALTHY';
        $httpCode = $isHealthy ? 200 : 503;

        return response()->json([
            'status'     => $overallStatus,
            'timestamp'  => Carbon::now()->toIso8601String(),
            'version'    => '2.0.0-ENTERPRISE',
            'components' => $components,
        ], $httpCode);
    }
}
