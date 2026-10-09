<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * gzip for large HTML/JSON responses. The dashboard is a ~2MB single page (all sections are rendered
 * server-side), which gzip shrinks roughly tenfold. Skipped when the web server already encoded the
 * response, for streamed/file responses, and for small bodies.
 */
class CompressResponse
{
    private const MIN_BYTES = 2048;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!function_exists('gzencode')
            || !($response instanceof LaravelResponse || $response instanceof JsonResponse)
            || $response->headers->has('Content-Encoding')
            || $response->getStatusCode() !== 200
            || !str_contains((string) $request->headers->get('Accept-Encoding'), 'gzip')
        ) {
            return $response;
        }

        $type = (string) $response->headers->get('Content-Type');
        if (!str_starts_with($type, 'text/html') && !str_starts_with($type, 'application/json')) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || strlen($content) < self::MIN_BYTES) {
            return $response;
        }

        $compressed = gzencode($content, 5);
        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->headers->set('Vary', 'Accept-Encoding', false);

        return $response;
    }
}
