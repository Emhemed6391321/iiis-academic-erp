<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SystemChangelog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Every release shows its latest published version next to the updates log in the sidebar,
 * and every SOP navigation button calls a method that the dashboard actually defines.
 */
class ReleaseVersionAndSopTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pre-existing SOP buttons whose target method does not exist in the dashboard (they do nothing when clicked).
     * Listed explicitly so they stay visible; any new broken button fails the test.
     */
    private const KNOWN_MISSING_SOP_ACTIONS = [
        'openNewStudentModal', 'loadGradeBatches', 'loadTranscriptsView', 'loadRolloverCenter', 'loadBranchesData',
        'loadContractsData', 'loadAssessmentsData', 'loadPermissionsMatrix', 'loadBackups', 'loadSettingsModule',
        'loadSignatories', 'loadInstituteProfile', 'loadSystemErrors',
    ];

    private function release(string $version, string $deployedAt, bool $published = true): void
    {
        SystemChangelog::create([
            'version' => $version,
            'title' => 'إصدار ' . $version,
            'description' => 'وصف اختباري',
            'type' => 'feature',
            'impact' => 'medium',
            'author' => 'اختبار',
            'is_published' => $published,
            'deployed_at' => $deployedAt,
        ]);
    }

    public function test_latest_published_version_is_the_newest_published_release(): void
    {
        Cache::forget('changelog.latest_version');
        $this->release('2.4.1', '2026-10-02 00:00:00');
        $this->release('2.5.0', '2026-10-17 00:00:00');
        $this->release('2.6.0', '2026-10-20 00:00:00', published: false);

        $this->assertSame('2.5.0', SystemChangelog::latestPublishedVersion());
    }

    public function test_sidebar_shows_the_latest_published_version_next_to_the_updates_log(): void
    {
        Cache::forget('changelog.latest_version');
        $this->release('2.5.0', '2026-10-17 00:00:00');

        $role = Role::create(['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']);
        $user = User::factory()->create(['role_id' => $role->id, 'must_change_password' => false]);

        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('سجل التحديثات', $html);
        $this->assertStringContainsString('v2.5.0', $html);
        $this->assertStringNotContainsString('>v2.4<', $html);
    }

    public function test_every_sop_navigation_action_is_defined_by_the_dashboard(): void
    {
        $sop = file_get_contents(resource_path('views/dashboard/sections/procedures.blade.php'));
        preg_match_all("/targetAction: '([A-Za-z_\$][\w\$]*)\(\)'/", $sop, $actions);
        $this->assertNotEmpty($actions[1]);

        $scripts = '';
        foreach ((new Finder())->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            $scripts .= $file->getContents();
        }

        $missing = [];
        foreach (array_diff(array_unique($actions[1]), self::KNOWN_MISSING_SOP_ACTIONS) as $method) {
            // a method definition in the dashboard script: "name(" at the start of a line, optionally async
            if (!preg_match('/^\s*(?:async\s+)?' . preg_quote($method, '/') . '\s*\([^)]*\)\s*\{/m', $scripts)) {
                $missing[] = $method;
            }
        }

        $this->assertSame([], $missing, 'SOP buttons call methods that are not defined: ' . implode(', ', $missing));
    }
}
