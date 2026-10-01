<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Providers\AppServiceProvider;
use App\Http\Middleware\ShieldedFeatureMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityDevBypassProductionBlockTest extends TestCase
{
    public function test_application_refuses_to_boot_in_production_if_dev_bypass_secret_is_set()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CRITICAL SECURITY VIOLATION: DEV_BYPASS is strictly prohibited in production environment.');

        // Simulate production environment
        $this->app['env'] = 'production';
        config(['app.dev_bypass_secret' => 'some_secret_token_12345']);

        // Boot provider to trigger gate
        $provider = new AppServiceProvider($this->app);
        $provider->boot();
    }

    public function test_shielded_feature_middleware_blocks_bypass_in_production_even_with_valid_secret()
    {
        $this->app['env'] = 'production';
        config(['app.dev_bypass_secret' => 'super_secret']);

        $middleware = new ShieldedFeatureMiddleware();
        $request = Request::create('/api/v1/study-and-exams/action', 'GET');
        $request->headers->set('X-Bypass-Shield', 'super_secret');

        $response = $middleware->handle($request, function () {
            return response('SUCCESS', 200);
        });

        $this->assertEquals(423, $response->getStatusCode());
        $this->assertStringContainsString('shielded', (string) $response->getContent());
    }

    public function test_shielded_feature_middleware_blocks_request_with_invalid_secret_in_local()
    {
        $this->app['env'] = 'local';
        config(['app.dev_bypass_secret' => 'correct_secret']);

        $middleware = new ShieldedFeatureMiddleware();
        $request = Request::create('/api/v1/study-and-exams/action', 'GET');
        $request->headers->set('X-Bypass-Shield', 'wrong_secret');

        $response = $middleware->handle($request, function () {
            return response('SUCCESS', 200);
        });

        $this->assertEquals(423, $response->getStatusCode());
    }

    public function test_shielded_feature_middleware_allows_bypass_only_in_local_with_exact_secret()
    {
        $this->app['env'] = 'local';
        config(['app.dev_bypass_secret' => 'correct_secret']);

        $middleware = new ShieldedFeatureMiddleware();
        $request = Request::create('/api/v1/study-and-exams/action', 'GET');
        $request->headers->set('X-Bypass-Shield', 'correct_secret');

        $response = $middleware->handle($request, function () {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
