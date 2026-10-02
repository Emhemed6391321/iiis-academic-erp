<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShieldedFeatureMiddleware
{
    /**
     * Handle an incoming request.
     * Intercept and shield features currently in "قريباً / قيد التطوير" mode.
     *
     * MED-3 FIX: Bypass is restricted to local/testing environment only,
     * and requires a secret token instead of a static known header value.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Strictly forbid outside local/testing environments
        if (!app()->environment('local', 'testing')) {
            return response()->json([
                'status'  => 'shielded',
                'message' => 'هذه الوحدة قيد التطوير والترقية حالياً وستكون متاحة قريباً وفق خطة التطوير الشاملة لمنظومة «المعهد التخصصي للعلوم الشرعية».',
                'feature' => 'STUDY_AND_EXAMS_SHIELDED',
            ], 423);
        }

        $bypassSecret = config('app.dev_bypass_secret', '__UNSET__');
        $canBypass = ($bypassSecret !== '__UNSET__' && !empty($bypassSecret))
            && hash_equals((string) $bypassSecret, (string) $request->header('X-Bypass-Shield'));

        if (!$canBypass) {
            return response()->json([
                'status'  => 'shielded',
                'message' => 'هذه الوحدة قيد التطوير والترقية حالياً وستكون متاحة قريباً وفق خطة التطوير الشاملة لمنظومة «المعهد التخصصي للعلوم الشرعية».',
                'feature' => 'STUDY_AND_EXAMS_SHIELDED',
            ], 423);
        }

        return $next($request);
    }
}
