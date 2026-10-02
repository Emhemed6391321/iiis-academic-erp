<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\BranchClass;

class BranchFullEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    protected function createSuperAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير النظام الرئيسي', 'is_global' => true]);
        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_can_update_branch_with_social_media_and_staff_info()
    {
        $admin = $this->createSuperAdmin();
        $branch = Branch::create([
            'name' => 'فرع طرابلس المركزي',
            'code' => 'TIP-01',
            'city' => 'طرابلس',
            'branch_status' => 'ACTIVE'
        ]);

        $updatePayload = [
            'name' => 'فرع طرابلس المركزي المحدث',
            'code' => 'TIP-01',
            'city' => 'طرابلس',
            'region' => 'المنطقة الغربية',
            'branch_status' => 'ACTIVE',
            'building_type' => 'owned',
            'building_condition' => 'excellent',
            'manager_name' => 'أ. عبد السلام الطرابلسي',
            'manager_phone' => '0912345678',
            'manager_email' => 'manager.tripoli@iiis.edu.ly',
            'phone' => '021-3344556',
            'email' => 'tripoli@iiis.edu.ly',
            'address' => 'شارع الجمهورية، قرب ميدان الشهداء',
            'latitude' => 32.8872,
            'longitude' => 13.1913,
            'academic_staff' => 25,
            'admin_staff' => 8,
            'facebook_url' => 'https://facebook.com/iiis.tripoli',
            'telegram_url' => 'https://t.me/iiis_tripoli',
            'whatsapp_number' => '+218912345678',
            'website_url' => 'https://tripoli.iiis.edu.ly',
            'notes' => 'مقر نموذجي معتمد'
        ];

        $response = $this->actingAs($admin)->putJson("/api/v1/branches/{$branch->id}", $updatePayload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'فرع طرابلس المركزي المحدث',
            'facebook_url' => 'https://facebook.com/iiis.tripoli',
            'whatsapp_number' => '+218912345678',
            'academic_staff' => 25,
            'admin_staff' => 8
        ]);
    }

    public function test_can_upload_and_delete_branch_photos()
    {
        $admin = $this->createSuperAdmin();
        $branch = Branch::create([
            'name' => 'فرع مصراتة',
            'code' => 'MIS-01',
            'city' => 'مصراتة'
        ]);

        $file = UploadedFile::fake()->create('facade.jpg', 100, 'image/jpeg');

        // Upload Photo
        $uploadResponse = $this->actingAs($admin)->postJson("/api/v1/branches/{$branch->id}/photos", [
            'photo' => $file,
            'caption' => 'الواجهة الرئيسية للمبنى',
            'category' => 'exterior'
        ]);

        $uploadResponse->assertStatus(200);
        $uploadResponse->assertJson(['status' => 'success']);

        $branch->refresh();
        $this->assertIsArray($branch->photos);
        $this->assertCount(1, $branch->photos);
        $this->assertEquals('الواجهة الرئيسية للمبنى', $branch->photos[0]['caption']);

        // Delete Photo
        $deleteResponse = $this->actingAs($admin)->deleteJson("/api/v1/branches/{$branch->id}/photos/0");
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson(['status' => 'success']);

        $branch->refresh();
        $this->assertCount(0, $branch->photos);
    }

    public function test_can_crud_branch_classes_and_lecture_halls()
    {
        $admin = $this->createSuperAdmin();
        $branch = Branch::create([
            'name' => 'فرع بنغازي',
            'code' => 'BEN-01',
            'city' => 'بنغازي'
        ]);

        // 1. Create Hall
        $createResponse = $this->actingAs($admin)->postJson("/api/v1/branches/{$branch->id}/classes", [
            'name' => 'قاعة الإمام نافع',
            'stage' => 'السنة الأولى',
            'room_type' => 'مدرج محاضرات',
            'floor' => 'الطابق الأول',
            'max_capacity' => 45,
            'status' => 'active',
            'equipment' => 'شاشة ذكية، تكييف مركزي، 45 مقعد'
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJson(['status' => 'success']);
        $hallId = $createResponse->json('data.id');

        $this->assertDatabaseHas('branch_classes', [
            'id' => $hallId,
            'branch_id' => $branch->id,
            'name' => 'قاعة الإمام نافع',
            'max_capacity' => 45
        ]);

        // 2. Update Hall
        $updateResponse = $this->actingAs($admin)->putJson("/api/v1/branches/{$branch->id}/classes/{$hallId}", [
            'name' => 'مدرج الإمام نافع الرئيسي',
            'stage' => 'السنة الأولى والثانية',
            'room_type' => 'مدرج محاضرات',
            'floor' => 'الطابق الأول',
            'max_capacity' => 60,
            'status' => 'active',
            'equipment' => 'شاشة ذكية، تكييف مركزي، 60 مقعد'
        ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('branch_classes', [
            'id' => $hallId,
            'name' => 'مدرج الإمام نافع الرئيسي',
            'max_capacity' => 60
        ]);

        // 3. Delete Hall
        $deleteResponse = $this->actingAs($admin)->deleteJson("/api/v1/branches/{$branch->id}/classes/{$hallId}");
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('branch_classes', ['id' => $hallId]);
    }
}
