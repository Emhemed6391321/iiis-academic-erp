<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content-Security-Policy.
        // script-src still needs 'unsafe-inline'/'unsafe-eval': the dashboard ships large inline scripts and
        // Alpine.js' standard build compiles x-* expressions with new Function(). Removing both means moving to
        // nonces + the @alpinejs/csp build. Everything else is closed down to what the pages actually load:
        // Tailwind/Alpine/Leaflet/xlsx CDNs (fallbacks), Google Fonts, OSM map tiles and the QR image service.
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://unpkg.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com https://unpkg.com; " .
               "img-src 'self' data: blob: https://*.tile.openstreetmap.org https://api.qrserver.com https://unpkg.com; " .
               "font-src 'self' data: https://fonts.gstatic.com; " .
               "connect-src 'self'; " .
               "frame-src 'self' blob: data:; " .
               "worker-src 'self' blob:; " .
               "manifest-src 'self'; " .
               "media-src 'self' blob: data:; " .
               "object-src 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self'; " .
               "frame-ancestors 'self';";
        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
