<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * فحص شامل: كل مسار GET في /api/v1 بلا معاملات يُستدعى بحساب المدير العام وبحساب مدير فرع،
 * ولا يجوز أن يعيد أي منهما خطأ خادم (5xx). الوحدات المغلقة تعيد 423 وهذا مقبول.
 */
class ApiRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);
    }

    private function parameterlessGetRoutes(): array
    {
        $uris = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!str_starts_with($uri, 'api/v1/') || str_contains($uri, '{') || !in_array('GET', $route->methods(), true)) {
                continue;
            }
            $uris[] = '/' . $uri;
        }

        return array_values(array_unique($uris));
    }

    public function test_no_parameterless_api_route_answers_with_a_server_error(): void
    {
        $uris = $this->parameterlessGetRoutes();
        $this->assertGreaterThan(40, count($uris), 'The smoke test must cover the API surface.');

        $failures = [];
        foreach (['admin@iiis.sch.ly', 'manager.tip@iiis.sch.ly', 'registrar.tip@iiis.sch.ly', 'students.hq@iiis.sch.ly'] as $email) {
            $user = User::where('email', $email)->firstOrFail();
            foreach ($uris as $uri) {
                $status = $this->actingAs($user)->getJson($uri)->getStatusCode();
                if ($status >= 500) {
                    $failures[] = "$email $uri => $status";
                }
            }
        }

        $this->assertSame([], $failures, "Server errors:\n" . implode("\n", $failures));
    }

    public function test_the_dashboard_pages_render_for_every_seeded_role(): void
    {
        foreach (User::all() as $user) {
            $status = $this->actingAs($user)->get('/')->getStatusCode();
            $this->assertContains($status, [200, 302], $user->email . ' answered ' . $status);
        }
    }
}
