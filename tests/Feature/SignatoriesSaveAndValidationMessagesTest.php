<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two defects seen in production: saving the signatories with an empty list was refused, and validation
 * failures showed raw message keys such as "validation.required" because the app had no translations.
 */
class SignatoriesSaveAndValidationMessagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']);

        return User::factory()->create(['role_id' => $role->id, 'must_change_password' => false]);
    }

    public function test_saving_an_empty_signatories_list_is_accepted_and_changes_nothing(): void
    {
        $response = $this->actingAs($this->admin())->postJson('/api/v1/admin/settings/signatories/save', [
            'signatories' => [],
        ]);

        $response->assertOk();
        $this->assertSame(0, \App\Models\OfficialDocumentSignatory::count());
    }

    public function test_validation_messages_are_readable_text_not_translation_keys(): void
    {
        $response = $this->actingAs($this->admin())->postJson('/api/v1/admin/settings/signatories/save', [
            'signatories' => [['slot_key' => 'prepared_by']],
        ]);

        $response->assertStatus(422);
        $errors = collect($response->json('errors'))->flatten()->implode(' | ');
        $this->assertNotSame('', $errors);
        $this->assertStringNotContainsString('validation.', $errors);
    }
}
