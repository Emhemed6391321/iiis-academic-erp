<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\AdministrativeSetting;
use App\Services\AdminSettingsService;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function createSuperAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام الرئيسي', 'is_global' => true]);
        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_can_update_and_persist_institute_profile()
    {
        $admin = $this->createSuperAdmin();

        $payload = [
            'state_name' => 'دولة ليبيا - حكومة الوحدة الوطنية',
            'supervising_body' => 'وزارة التعليم والتربية',
            'supervising_department' => 'إدارة المعاهد الدينية',
            'institute_name' => 'معهد الإمام مالك للعلوم الشرعية',
            'branch_label' => 'المقر المركزي بطرابلس',
            'phone' => '+218 21 111 2222',
            'email' => 'admin@malik-institute.edu.ly',
            'address' => 'ميدان الشهداء، طرابلس',
            'website' => 'https://malik-institute.edu.ly',
            'pobox' => 'ص.ب 999',
            'header_title' => 'معهد الإمام مالك',
            'footer_text' => 'جميع الحقوق محفوظة للمعهد 2026'
        ];

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/settings/institute-profile', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Check in database
        $this->assertDatabaseHas('administrative_settings', [
            'key' => 'institute_name',
            'value' => 'معهد الإمام مالك للعلوم الشرعية'
        ]);
        $this->assertDatabaseHas('administrative_settings', [
            'key' => 'state_name',
            'value' => 'دولة ليبيا - حكومة الوحدة الوطنية'
        ]);

        // Check getMasterSettings API
        $getResponse = $this->actingAs($admin)->getJson('/api/v1/admin/settings/all');
        $getResponse->assertStatus(200);
        $this->assertEquals('معهد الإمام مالك للعلوم الشرعية', $getResponse->json('profile.institute_name'));
        $this->assertEquals('دولة ليبيا - حكومة الوحدة الوطنية', $getResponse->json('profile.state_name'));
    }
}
