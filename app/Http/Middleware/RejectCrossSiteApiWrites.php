<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CSRF defence for cookie-authenticated API routes (the api group has no VerifyCsrfToken).
 * State-changing requests must come from this origin; browsers always send Origin and/or
 * Sec-Fetch-Site on cross-site requests, and non-browser clients send neither.
 */
class RejectCrossSiteApiWrites
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $fetchSite = $request->headers->get('Sec-Fetch-Site');
        if ($fetchSite !== null && !in_array($fetchSite, ['same-origin', 'none'], true)) {
            return $this->deny();
        }

        $origin = $request->headers->get('Origin');
        if ($origin !== null && $origin !== 'null' && parse_url($origin, PHP_URL_HOST) !== $request->getHost()) {
            return $this->deny();
        }
        if ($origin === 'null') {
            return $this->deny();
        }

        return $next($request);
    }

    private function deny(): Response
    {
        return response()->json([
            'success' => false,
            'code' => 'CROSS_SITE_REQUEST_BLOCKED',
            'message' => 'تم رفض الطلب: مصدر الطلب غير موثوق.',
        ], 403);
    }
}
