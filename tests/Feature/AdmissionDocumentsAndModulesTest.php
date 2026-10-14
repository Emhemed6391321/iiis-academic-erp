<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StudentDocument;
use App\Models\StudyYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * نموذج القبول الموحد، المستندات المشفرة، ومفاتيح تفعيل الوحدات.
 */
class AdmissionDocumentsAndModulesTest extends TestCase
{
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\nadmission document\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_super_admin_holds_every_permission_including_matrix_management(): void
    {
        $admin = Role::where('name', 'super_admin')->firstOrFail();

        $this->assertSame(Permission::count(), $admin->permissions()->count());
        $this->assertTrue($admin->permissions()->where('code', 'permissions.manage')->exists());
    }

    public function test_form_options_are_all_branches_for_hq_and_own_branch_for_branch_manager(): void
    {
        $hq = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();
        $hqBranches = $this->actingAs($hq)->getJson('/api/v1/students/form-options')
            ->assertOk()
            ->json('branches');
        $this->assertGreaterThan(1, count($hqBranches));

        $manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $ownBranches = $this->actingAs($manager)->getJson('/api/v1/students/form-options')
            ->assertOk()
            ->json('branches');
        $this->assertSame([$manager->branch_id], array_column($ownBranches, 'id'));
    }

    public function test_admission_attachments_are_stored_in_vault_under_canonical_types(): void
    {
        $manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $payload = $this->admissionPayload($manager->branch_id, '119912340001');
        $payload['attached_documents'] = [
            ['type' => 'شهادة إتمام المرحلة الأساسية', 'name' => 'basic.pdf', 'base64' => 'data:application/pdf;base64,' . base64_encode(self::PDF)],
        ];

        $studentId = $this->actingAs($manager)->postJson('/api/v1/students', $payload)
            ->assertCreated()
            ->json('data.id');

        $doc = StudentDocument::where('student_id', $studentId)->firstOrFail();
        $this->assertSame('BASIC_EDUCATION_CERT', $doc->document_type);
        $this->assertStringStartsWith('secure_vault/', $doc->file_path);
        Storage::disk('local')->assertExists($doc->file_path);
        $this->assertNotEmpty($doc->file_url);
    }

    public function test_document_upload_accepts_captured_photo_and_links_profile_photo(): void
    {
        $manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $studentId = $this->createStudent($manager, '119912340002');

        $jpeg = "\xFF\xD8\xFF\xE0" . str_repeat("\0", 64);
        $this->actingAs($manager)->postJson("/api/v1/students/{$studentId}/file/documents", [
            'document_type' => 'PERSONAL_PHOTO',
            'captured_photo' => 'data:image/jpeg;base64,' . base64_encode($jpeg),
        ])->assertOk()->assertJsonPath('success', true);

        $doc = StudentDocument::where('student_id', $studentId)->where('document_type', 'PERSONAL_PHOTO')->firstOrFail();
        Storage::disk('local')->assertExists($doc->file_path);

        $student = \App\Models\Student::findOrFail($studentId);
        $this->assertSame($doc->file_path, $student->profile_photo_path);
        $this->assertNotNull($student->profile_photo_url);
    }

    public function test_replacing_a_document_keeps_one_record_and_removes_the_old_file(): void
    {
        $manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $studentId = $this->createStudent($manager, '119912340003');

        $this->actingAs($manager)->post("/api/v1/students/{$studentId}/file/documents", [
            'document_type' => 'BIRTH_CERT',
            'document' => UploadedFile::fake()->createWithContent('birth-1.pdf', self::PDF),
        ])->assertOk();
        $oldPath = StudentDocument::where('student_id', $studentId)->where('document_type', 'BIRTH_CERT')->value('file_path');

        $this->actingAs($manager)->post("/api/v1/students/{$studentId}/file/documents", [
            'document_type' => 'BIRTH_CERT',
            'document' => UploadedFile::fake()->createWithContent('birth-2.pdf', self::PDF . "\nv2"),
        ])->assertOk()->assertJsonPath('message', 'تم استبدال وتحديث المستند بنجاح.');

        $this->assertSame(1, StudentDocument::where('student_id', $studentId)->where('document_type', 'BIRTH_CERT')->count());
        Storage::disk('local')->assertMissing($oldPath);
    }

    public function test_document_larger_than_ten_megabytes_is_rejected(): void
    {
        $manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $studentId = $this->createStudent($manager, '119912340004');

        $this->actingAs($manager)->withHeaders(['Accept' => 'application/json'])->post("/api/v1/students/{$studentId}/file/documents", [
            'document_type' => 'OTHER',
            'notes' => 'كبير',
            'document' => UploadedFile::fake()->create('huge.pdf', 11 * 1024, 'application/pdf'),
        ])->assertStatus(422);

        $this->assertSame(0, StudentDocument::where('student_id', $studentId)->count());
    }

    public function test_module_switches_are_readable_by_all_and_changeable_only_by_general_director(): void
    {
        $manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();
        $admin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();

        $this->actingAs($manager)->getJson('/api/v1/modules/states')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($manager)->postJson('/api/v1/modules/states', ['locked' => []])
            ->assertForbidden();

        $this->actingAs($admin)->postJson('/api/v1/modules/states', ['locked' => ['grading']])
            ->assertOk()
            ->assertJsonPath('locked', ['grading']);

        $this->actingAs($manager)->getJson('/api/v1/modules/states')
            ->assertJsonPath('locked', ['grading']);
    }

    public function test_module_switch_rejects_unknown_module_keys(): void
    {
        $admin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();

        $this->actingAs($admin)->postJson('/api/v1/modules/states', ['locked' => ['not_a_module']])
            ->assertStatus(422);
    }

    public function test_dashboard_renders_admission_and_document_controls_for_general_director(): void
    {
        $admin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();

        $this->actingAs($admin)->get('/')
            ->assertOk()
            ->assertSee('admissionOptions', false)
            ->assertSee('docPreviewModal', false)
            ->assertSee('isModuleLocked', false);
    }

    private function createStudent(User $actor, string $nationalId): int
    {
        return $this->actingAs($actor)->postJson('/api/v1/students', $this->admissionPayload($actor->branch_id, $nationalId))
            ->assertCreated()
            ->json('data.id');
    }

    private function admissionPayload(?int $branchId, string $nationalId): array
    {
        return [
            'first_name' => 'اختبار', 'father_name' => 'أب', 'grandfather_name' => 'جد', 'family_name' => 'عائلة',
            'mother_name' => 'أم', 'national_id' => $nationalId, 'ministry_student_id' => '',
            'gender' => 'MALE', 'birth_date' => '2006-01-01', 'birth_place' => 'بنغازي', 'nationality' => 'ليبي',
            'phone' => '0910000000', 'guardian_phone' => '0920000000', 'branch_id' => $branchId,
            'department_id' => Department::firstOrFail()->id,
            'current_study_year_id' => StudyYear::orderBy('level_order')->value('id'),
            'study_type' => 'REGULAR', 'blood_type' => 'O+', 'has_disability' => false,
            'chronic_diseases_list' => [], 'chronic_diseases' => '', 'notes' => '',
        ];
    }
}
