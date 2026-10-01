<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LoginSecurityService
{
    private const TIER_1_ATTEMPTS = 5;
    private const TIER_1_DECAY = 60; // 1 minute

    private const TIER_2_ATTEMPTS = 8;
    private const TIER_2_DECAY = 900; // 15 minutes

    private const TIER_3_ATTEMPTS = 12;
    private const TIER_3_DECAY = 86400; // 24 hours

    /**
     * Check if account or IP is currently locked out.
     * 
     * @return array{is_locked: bool, seconds: int, type: string, message: string|null}
     */
    public function checkLockout(string $login, string $ip): array
    {
        $accountKey = $this->accountLockKey($login);
        $ipKey = $this->ipLockKey($ip);

        $accountLockExpiry = Cache::get($accountKey);
        $ipLockExpiry = Cache::get($ipKey);

        $now = time();

        if ($accountLockExpiry && $accountLockExpiry > $now) {
            $seconds = $accountLockExpiry - $now;
            return [
                'is_locked' => true,
                'seconds' => $seconds,
                'type' => 'ACCOUNT',
                'message' => "تم قفل الحساب مؤقتاً لحمايته بسبب تكرار المحاولات الفاشلة. يرجى الانتظار {$this->formatDuration($seconds)}.",
            ];
        }

        if ($ipLockExpiry && $ipLockExpiry > $now) {
            $seconds = $ipLockExpiry - $now;
            return [
                'is_locked' => true,
                'seconds' => $seconds,
                'type' => 'IP',
                'message' => "تم حظر عنوان الشبكة (IP) مؤقتاً لأسباب أمنية. يرجى الانتظار {$this->formatDuration($seconds)}.",
            ];
        }

        return [
            'is_locked' => false,
            'seconds' => 0,
            'type' => 'NONE',
            'message' => null,
        ];
    }

    /**
     * Record a failed login attempt and apply progressive lockout if thresholds exceeded.
     */
    public function recordFailedAttempt(string $login, string $ip): array
    {
        $accountCounterKey = $this->accountCounterKey($login);
        $ipCounterKey = $this->ipCounterKey($ip);

        $accountAttempts = (int) Cache::get($accountCounterKey, 0) + 1;
        $ipAttempts = (int) Cache::get($ipCounterKey, 0) + 1;

        // Persist attempts counter with 24 hours window
        Cache::put($accountCounterKey, $accountAttempts, 86400);
        Cache::put($ipCounterKey, $ipAttempts, 86400);

        // Evaluate progressive lockout for account
        $this->evaluateLockout($this->accountLockKey($login), $accountAttempts);

        // Evaluate progressive lockout for IP
        $this->evaluateLockout($this->ipLockKey($ip), $ipAttempts);

        return [
            'account_attempts' => $accountAttempts,
            'ip_attempts' => $ipAttempts,
        ];
    }

    /**
     * Clear lockout and attempts on successful login.
     */
    public function clearAttempts(string $login, string $ip): void
    {
        Cache::forget($this->accountCounterKey($login));
        Cache::forget($this->ipCounterKey($ip));
        Cache::forget($this->accountLockKey($login));
        Cache::forget($this->ipLockKey($ip));
    }

    private function evaluateLockout(string $lockKey, int $attempts): void
    {
        $decay = 0;

        if ($attempts >= self::TIER_3_ATTEMPTS) {
            $decay = self::TIER_3_DECAY;
        } elseif ($attempts >= self::TIER_2_ATTEMPTS) {
            $decay = self::TIER_2_DECAY;
        } elseif ($attempts >= self::TIER_1_ATTEMPTS) {
            $decay = self::TIER_1_DECAY;
        }

        if ($decay > 0) {
            Cache::put($lockKey, time() + $decay, $decay);
        }
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} ثانية";
        }
        if ($seconds < 3600) {
            $mins = ceil($seconds / 60);
            return "{$mins} دقيقة";
        }
        $hours = ceil($seconds / 3600);
        return "{$hours} ساعة";
    }

    private function accountLockKey(string $login): string
    {
        return 'sec:lock:acc:' . hash('sha256', strtolower(trim($login)));
    }

    private function ipLockKey(string $ip): string
    {
        return 'sec:lock:ip:' . hash('sha256', trim($ip));
    }

    private function accountCounterKey(string $login): string
    {
        return 'sec:cnt:acc:' . hash('sha256', strtolower(trim($login)));
    }

    private function ipCounterKey(string $ip): string
    {
        return 'sec:cnt:ip:' . hash('sha256', trim($ip));
    }
}
