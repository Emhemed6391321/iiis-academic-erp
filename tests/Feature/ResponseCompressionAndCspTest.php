<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponseCompressionAndCspTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();
        User::query()->update(['must_change_password' => false]);

        return User::where('email', 'admin@iiis.sch.ly')->first();
    }

    public function test_dashboard_is_gzipped_when_the_client_accepts_it(): void
    {
        $res = $this->actingAs($this->admin())->get('/', ['Accept-Encoding' => 'gzip, deflate']);

        $res->assertOk()->assertHeader('Content-Encoding', 'gzip');
        $html = gzdecode($res->baseResponse->getContent());
        $this->assertIsString($html);
        $this->assertStringContainsString('<html', $html);
        $this->assertLessThan(strlen($html) / 3, strlen($res->baseResponse->getContent()), 'gzip should shrink the page substantially');
    }

    public function test_responses_are_not_compressed_without_accept_encoding(): void
    {
        $res = $this->actingAs($this->admin())->get('/');

        $res->assertOk();
        $this->assertNull($res->headers->get('Content-Encoding'));
    }

    public function test_csp_no_longer_allows_arbitrary_https_sources(): void
    {
        $csp = $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self';", $csp);
        $this->assertStringContainsString("connect-src 'self';", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertDoesNotMatchRegularExpression('/(?<!\.)\bhttps:(?!\/\/)/', $csp, 'bare https: wildcard source');
    }
}
