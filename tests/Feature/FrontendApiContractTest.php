<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Every literal fetch('/api/v1/...') in the dashboard views must hit a registered route with the
 * HTTP method the page uses. Catches UI calls pointing at endpoints that do not exist
 * (e.g. /profile/password vs /user/password), which otherwise only fail at click time.
 */
class FrontendApiContractTest extends TestCase
{
    /** Known gaps with no backend implementation yet. Remove an entry when the endpoint is built. */
    private const KNOWN_MISSING = [
        'POST api/v1/settings/central-settings',
    ];

    private function normalise(string $uri): string
    {
        return preg_replace('/\{[^}]+\}/', '{p}', trim($uri, '/'));
    }

    /** @return array<string, string[]> normalised uri => methods */
    private function routeTable(): array
    {
        $table = [];
        foreach (Route::getRoutes() as $route) {
            foreach ($route->methods() as $method) {
                if ($method !== 'HEAD') {
                    $table[$this->normalise($route->uri())][] = $method;
                }
            }
        }

        return $table;
    }

    public function test_dashboard_fetch_calls_match_registered_api_routes(): void
    {
        $table = $this->routeTable();
        $problems = [];
        $checked = 0;

        $files = (new Finder())->files()->in(resource_path('views'))->name('*.blade.php');
        foreach ($files as $file) {
            $src = $file->getContents();
            preg_match_all('/fetch\(\s*([`\'"])(\/api\/v1\/[^`\'"?]*)(?:\?[^`\'"]*)?\1\s*(?:,\s*\{(.{0,260}))?/s', $src, $matches, PREG_SET_ORDER);

            foreach ($matches as $m) {
                $url = $m[2];
                // URLs built by concatenation ('/api/v1/x/' + id + '/y') or with a dynamic tail cannot be checked statically.
                if (str_ends_with($url, '/') || str_ends_with($url, '}')) {
                    continue;
                }

                $method = preg_match('/method:\s*[\'"](\w+)[\'"]/', $m[3] ?? '', $mm) ? strtoupper($mm[1]) : 'GET';
                $uri = $this->normalise(preg_replace('/\$\{[^}]+\}/', '{p}', ltrim($url, '/')));
                $checked++;

                if (in_array("{$method} {$uri}", self::KNOWN_MISSING, true)) {
                    continue;
                }

                $allowed = $table[$uri] ?? null;
                $methodOk = $allowed && (in_array($method, $allowed, true)
                    || (in_array($method, ['PUT', 'PATCH'], true) && array_intersect(['PUT', 'PATCH'], $allowed)));

                if (!$methodOk) {
                    $problems[] = "{$file->getFilename()}: {$method} /{$uri} -> " . ($allowed ? 'route allows ' . implode(',', array_unique($allowed)) : 'no such route');
                }
            }
        }

        $this->assertGreaterThan(100, $checked, 'the scanner found suspiciously few fetch calls');
        $this->assertSame([], $problems, "UI calls without a matching API route:\n" . implode("\n", $problems));
    }
}
