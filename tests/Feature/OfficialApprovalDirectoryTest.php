<?php

namespace Tests\Feature;

use App\Models\OfficialDocumentSignatory;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminSettingsService;
use Database\Seeders\OfficialSignatoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Names and titles on official documents are controlled only by the central signature directory:
 * an unset slot prints nothing, each switch hides the name or the title, and the seeder never overwrites an existing slot.
 */
class OfficialApprovalDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']);

        return User::factory()->create(['role_id' => $role->id, 'must_change_password' => false]);
    }

    public function test_an_unset_slot_yields_no_title_and_no_name(): void
    {
        $this->assertSame([], AdminSettingsService::getSignatoriesFor('enrollment_cert'));
    }

    public function test_the_title_switch_hides_the_title_and_keeps_the_name(): void
    {
        OfficialDocumentSignatory::create([
            'document_code' => 'conduct_cert',
            'slot_key' => 'prepared_by',
            'slot_label' => 'مسجل',
            'custom_title_override' => 'مسجل شؤون الطلبة',
            'is_active' => true,
            'show_name' => true,
            'show_title' => false,
        ]);

        $slot = AdminSettingsService::getSignatoriesFor('conduct_cert')['prepared_by'];
        $this->assertSame('', $slot['title']);
        $this->assertFalse($slot['show_title']);
    }

    public function test_the_name_switch_hides_the_name_and_keeps_the_title(): void
    {
        $role = Role::create(['name' => 'branch_registrar', 'display_name' => 'مسجل', 'scope_type' => 'BRANCH_SCOPE']);
        $user = User::factory()->create(['role_id' => $role->id, 'name' => 'اسم موظف اختباري']);
        OfficialDocumentSignatory::create([
            'document_code' => 'conduct_cert',
            'slot_key' => 'prepared_by',
            'slot_label' => 'مسجل',
            'user_id' => $user->id,
            'custom_title_override' => 'مسجل شؤون الطلبة',
            'is_active' => true,
            'show_name' => false,
            'show_title' => true,
        ]);

        $slot = AdminSettingsService::getSignatoriesFor('conduct_cert')['prepared_by'];
        $this->assertSame('', $slot['name']);
        $this->assertSame('مسجل شؤون الطلبة', $slot['title']);
    }

    public function test_saving_the_directory_stores_both_switches(): void
    {
        $this->actingAs($this->admin())->postJson('/api/v1/admin/settings/signatories/save', [
            'signatories' => [[
                'document_code' => 'secret_report',
                'slot_key' => 'approved_by',
                'slot_label' => 'يعتمد',
                'custom_title_override' => 'المدير العام للمعهد',
                'is_active' => true,
                'show_name' => false,
                'show_title' => true,
            ]],
        ])->assertOk();

        $row = OfficialDocumentSignatory::where('document_code', 'secret_report')->where('slot_key', 'approved_by')->firstOrFail();
        $this->assertFalse($row->show_name);
        $this->assertTrue($row->show_title);
    }

    public function test_seeder_creates_missing_slots_and_never_overwrites_an_existing_one(): void
    {
        // An existing slot edited on the server must survive the seeder.
        OfficialDocumentSignatory::create([
            'document_code' => 'data_quality',
            'slot_key' => 'prepared_by',
            'slot_label' => 'مسؤول معدّل',
            'custom_title_override' => 'صفة عدّلها المدير',
            'is_active' => true,
        ]);

        (new OfficialSignatoriesSeeder())->run();
        (new OfficialSignatoriesSeeder())->run();

        $this->assertSame('صفة عدّلها المدير', OfficialDocumentSignatory::where('document_code', 'data_quality')->where('slot_key', 'prepared_by')->value('custom_title_override'));
        $this->assertSame(3, OfficialDocumentSignatory::where('document_code', 'data_quality')->count());
        $this->assertSame(3, OfficialDocumentSignatory::where('document_code', 'enrollment_cert')->count());
    }

    public function test_no_printed_form_contains_the_old_institute_name_or_a_fixed_signatory_text(): void
    {
        $forbidden = ['المعهد المتوسط للدراسات الإسلامية', 'أ. مسجل عام المعهد', 'مسؤول شؤون الطلاب والحضور', 'مدير فرع المعهد التخصصي'];

        $hits = [];
        foreach ((new Finder())->files()->in([resource_path('views'), app_path()])->name(['*.blade.php', '*.php']) as $file) {
            $text = $file->getContents();
            foreach ($forbidden as $phrase) {
                if (str_contains($text, $phrase)) {
                    $hits[] = $file->getRelativePathname() . ': ' . $phrase;
                }
            }
        }

        $this->assertSame([], $hits, 'Fixed text found in printed forms: ' . implode(' | ', $hits));
    }
}
