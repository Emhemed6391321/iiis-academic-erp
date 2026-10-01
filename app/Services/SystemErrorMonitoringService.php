<?php

namespace App\Services;

use App\Models\SystemErrorLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Throwable;

class SystemErrorMonitoringService
{
    /**
     * Log a PHP Throwable/Exception.
     */
    public function recordThrowable(Throwable $exception, string $severity = 'HIGH', ?string $errorType = null): ?SystemErrorLog
    {
        try {
            $class = get_class($exception);
            if ($errorType === null) {
                if (str_contains($class, 'Database') || str_contains($class, 'PDO') || str_contains($class, 'QueryException')) {
                    $errorType = 'DATABASE_ERROR';
                    $severity = 'CRITICAL';
                } elseif (str_contains($class, 'Authentication') || str_contains($class, 'Authorization')) {
                    $errorType = 'AUTHENTICATION_ERROR';
                    $severity = 'MEDIUM';
                } elseif (str_contains($class, 'ValidationException')) {
                    $errorType = 'VALIDATION_ERROR';
                    $severity = 'LOW';
                } else {
                    $errorType = 'ERROR';
                }
            }

            $message = $this->sanitizeMessage($exception->getMessage());
            $file = $exception->getFile();
            $line = $exception->getLine();
            $stackTrace = $exception->getTraceAsString();

            return $this->recordLog([
                'error_type' => $errorType,
                'severity' => $severity,
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'stack_trace' => $stackTrace,
                'url' => Request::fullUrl(),
                'http_method' => Request::method(),
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'request_data' => $this->sanitizePayload(Request::all()),
            ]);
        } catch (Throwable $e) {
            // Fail silently to never crash the main application
            return null;
        }
    }

    /**
     * Record a JavaScript error reported from the client.
     */
    public function recordJsError(array $data): ?SystemErrorLog
    {
        try {
            $message = $this->sanitizeMessage($data['message'] ?? 'Unknown JavaScript Error');
            $file = $data['file'] ?? ($data['source'] ?? 'Browser Runtime');
            $line = isset($data['line']) ? (int) $data['line'] : null;
            $stackTrace = $data['stack'] ?? null;
            $url = $data['url'] ?? Request::header('referer', Request::fullUrl());

            return $this->recordLog([
                'error_type' => 'JAVASCRIPT_ERROR',
                'severity' => $data['severity'] ?? 'MEDIUM',
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'stack_trace' => $stackTrace,
                'url' => $url,
                'http_method' => 'CLIENT',
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'request_data' => $this->sanitizePayload($data['context'] ?? []),
            ]);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Core logging and aggregation logic.
     */
    public function recordLog(array $params): SystemErrorLog
    {
        $message = $params['message'] ?? 'Unspecified error';
        $file = $params['file'] ?? '';
        $line = $params['line'] ?? 0;
        $type = $params['error_type'] ?? 'ERROR';

        // Fingerprint hash for deduplication
        $errorHash = md5($type . '|' . $file . '|' . $line . '|' . substr($message, 0, 200));

        // Authenticated user details
        $user = Auth::user();
        $userId = $params['user_id'] ?? ($user ? $user->id : null);
        $userName = $params['user_name'] ?? ($user ? ($user->name ?? $user->email) : 'مستخدم غير مسجل / زائر');
        $branchId = $params['branch_id'] ?? ($user && isset($user->branch_id) ? $user->branch_id : null);
        $branchName = $params['branch_name'] ?? ($user && method_exists($user, 'branch') && $user->branch ? $user->branch->name : null);

        $now = now();

        // Check if matching unresolved/recent error exists
        $existing = SystemErrorLog::where('error_hash', $errorHash)->first();

        if ($existing) {
            $existing->increment('occurrences_count');
            $existing->update([
                'last_seen_at' => $now,
                'url' => $params['url'] ?? $existing->url,
                'user_id' => $userId ?? $existing->user_id,
                'user_name' => $userName ?? $existing->user_name,
                'branch_id' => $branchId ?? $existing->branch_id,
                'branch_name' => $branchName ?? $existing->branch_name,
                'ip_address' => $params['ip_address'] ?? $existing->ip_address,
                'user_agent' => $params['user_agent'] ?? $existing->user_agent,
                'request_data' => !empty($params['request_data']) ? $params['request_data'] : $existing->request_data,
                // Re-open if was resolved/ignored
                'status' => in_array($existing->status, ['RESOLVED', 'IGNORED']) ? 'NEW' : $existing->status,
            ]);

            return $existing;
        }

        // Create new error log entry
        $errorId = 'ERR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        return SystemErrorLog::create([
            'error_id' => $errorId,
            'error_hash' => $errorHash,
            'error_type' => $type,
            'severity' => $params['severity'] ?? 'HIGH',
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'stack_trace' => $params['stack_trace'] ?? null,
            'url' => $params['url'] ?? null,
            'page_context' => $params['page_context'] ?? ($params['page'] ?? null),
            'button_action' => $params['button_action'] ?? ($params['action'] ?? null),
            'http_method' => $params['http_method'] ?? 'GET',
            'user_id' => $userId,
            'user_name' => $userName,
            'branch_id' => $branchId,
            'branch_name' => $branchName,
            'ip_address' => $params['ip_address'] ?? null,
            'user_agent' => $params['user_agent'] ?? null,
            'request_data' => $params['request_data'] ?? null,
            'status' => 'NEW',
            'occurrences_count' => 1,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ]);
    }

    /**
     * Retrieve filtered list of error logs.
     */
    public function getFilteredLogs(array $filters = [])
    {
        $query = SystemErrorLog::query()->orderBy('last_seen_at', 'desc');

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('error_id', 'like', "%{$s}%")
                  ->orWhere('message', 'like', "%{$s}%")
                  ->orWhere('file', 'like', "%{$s}%")
                  ->orWhere('url', 'like', "%{$s}%")
                  ->orWhere('user_name', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['error_type'])) {
            $query->where('error_type', $filters['error_type']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('last_seen_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('last_seen_at', '<=', $filters['to_date']);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        return $query->paginate($perPage);
    }

    /**
     * Get summary KPI statistics for error monitoring dashboard.
     */
    public function getDashboardKPIs(): array
    {
        $today = now()->startOfDay();

        return [
            'total_errors' => SystemErrorLog::count(),
            'unresolved_count' => SystemErrorLog::whereIn('status', ['NEW', 'IN_REVIEW'])->count(),
            'critical_count' => SystemErrorLog::where('severity', 'CRITICAL')->whereIn('status', ['NEW', 'IN_REVIEW'])->count(),
            'today_count' => SystemErrorLog::where('last_seen_at', '>=', $today)->count(),
            'js_errors_count' => SystemErrorLog::where('error_type', 'JAVASCRIPT_ERROR')->count(),
            'db_errors_count' => SystemErrorLog::where('error_type', 'DATABASE_ERROR')->count(),
        ];
    }

    /**
     * Update error status with notes.
     */
    public function updateStatus(int $id, string $status, ?string $notes = null): ?SystemErrorLog
    {
        $log = SystemErrorLog::find($id);
        if (!$log) return null;

        $user = Auth::user();
        $log->update([
            'status' => $status,
            'resolved_by_user_id' => $user ? $user->id : null,
            'resolved_by_user_name' => $user ? ($user->name ?? $user->email) : 'مدير النظام',
            'resolved_at' => in_array($status, ['RESOLVED', 'IGNORED']) ? now() : null,
            'resolution_notes' => $notes,
        ]);

        return $log;
    }

    /**
     * Sanitize message to hide database passwords or sensitive connection strings.
     */
    protected function sanitizeMessage(string $msg): string
    {
        // Mask passwords in SQL connection strings e.g. password=xyz
        $msg = preg_replace('/(password|pwd|secret|token)\s*=\s*([^\s;]+)/i', '$1=********', $msg);
        return $msg;
    }

    /**
     * Sanitize array payload to remove passwords and secrets.
     */
    protected function sanitizePayload($data): array
    {
        if (!is_array($data)) return [];

        $sanitized = [];
        $sensitiveKeys = ['password', 'password_confirmation', 'secret', 'token', 'api_key', 'authorization', 'cookie'];

        foreach ($data as $k => $v) {
            if (in_array(strtolower($k), $sensitiveKeys)) {
                $sanitized[$k] = '********';
            } elseif (is_array($v)) {
                $sanitized[$k] = $this->sanitizePayload($v);
            } else {
                $sanitized[$k] = is_string($v) && strlen($v) > 500 ? substr($v, 0, 500) . '...[truncated]' : $v;
            }
        }

        return $sanitized;
    }
}
