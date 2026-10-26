<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeePlacement;
use App\Models\JobPosition;
use App\Models\OfficialDocumentSignatory;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\User;
use App\Services\AdminSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The signature directory drives the printed documents: a stopped slot disappears, an edited slot changes the next print,
 * a branch position never signs with another branch's holder, and the official stamp no longer exists anywhere.
 */
class SignatoryDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branch;
    private Student $student;
    private JobPosition $branchPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $superRole = Role::create(['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']);
        $this->admin = User::factory()->create(['role_id' => $superRole->id]);

        $registrarRole = Role::create(['name' => 'branch_registrar', 'display_name' => 'مسجل شؤون الطلبة', 'scope_type' => 'BRANCH_SCOPE']);
        AdminSettingsService::syncRolePositions();
        $this->branchPosition = JobPosition::where('role_id', $registrarRole->id)->firstOrFail();

        $this->branch = Branch::create(['name' => 'فرع التوقيع', 'code' => 'STP-01', 'city' => 'طرابلس', 'branch_status' => 'ACTIVE']);

        foreach (['enrollment_cert', 'conduct_cert'] as $code) {
            $this->slot($code, 'prepared_by', ['custom_title_override' => 'مسجل شؤون الطلبة المعتمد']);
            $this->slot($code, 'approved_by', ['custom_title_override' => 'مدير عام المعهد']);
        }
        AdminSettingsService::clearCache();

        $academicYear = AcademicYear::create([
            'name' => '2026/2027', 'code' => 'AY-2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true,
        ]);
        $studyYear = StudyYear::create(['name' => 'السنة الأولى', 'level_order' => 1]);
        $department = Department::create(['name' => 'الشريعة', 'code' => 'SHR-STP']);

        $this->student = Student::create([
            'first_name' => 'محمد', 'father_name' => 'أحمد', 'grandfather_name' => 'سالم', 'family_name' => 'الليبي',
            'mother_name' => 'فاطمة عمر', 'national_id' => '199912345678', 'birth_date' => '2005-01-01', 'birth_place' => 'طرابلس',
            'gender' => 'MALE', 'nationality' => 'ليبي', 'nationality_category' => 'LIBYAN',
            'phone' => '0912345678', 'guardian_phone' => '0923456789', 'branch_id' => $this->branch->id,
            'current_study_year_id' => $studyYear->id, 'enrolled_academic_year_id' => $academicYear->id,
            'academic_year_id' => $academicYear->id, 'department_id' => $department->id,
            'academic_status' => 'ENROLLED_ACTIVE', 'study_type' => 'REGULAR', 'is_archived' => false,
            'academic_number' => 'M26-0002',
        ]);
    }

    private function slot(string $code, string $slot, array $overrides = []): OfficialDocumentSignatory
    {
        return OfficialDocumentSignatory::updateOrCreate(
            ['document_code' => $code, 'slot_key' => $slot],
            array_merge(['slot_label' => $slot, 'is_active' => true], $overrides)
        );
    }

    private function certificate(string $kind): array
    {
        $response = $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/certificates/{$kind}");
        $response->assertOk();

        return $response->json('certificate');
    }

    public static function certificateKinds(): array
    {
        return [['enrollment', 'enrollment_cert'], ['conduct', 'conduct_cert']];
    }

    #[DataProvider('certificateKinds')]
    public function test_a_stopped_slot_drops_its_signature(string $kind, string $code): void
    {
        $this->slot($code, 'approved_by', ['is_active' => false]);

        $cert = $this->certificate($kind);

        $this->assertArrayNotHasKey('approved_by', $cert['signatories']);
        $this->assertArrayHasKey('prepared_by', $cert['signatories']);
    }

    #[DataProvider('certificateKinds')]
    public function test_editing_a_slot_from_the_settings_screen_changes_the_printed_document(string $kind, string $code): void
    {
        $rows = OfficialDocumentSignatory::where('document_code', $code)->get()->map(fn ($s) => [
            'document_code' => $s->document_code,
            'slot_key' => $s->slot_key,
            'slot_label' => $s->slot_label,
            'custom_title_override' => $s->slot_key === 'approved_by' ? 'مدير المعهد بالإنابة' : $s->custom_title_override,
            'is_active' => true,
            'show_name' => true,
            'show_title' => true,
        ])->values()->all();

        $this->actingAs($this->admin)->postJson('/api/v1/admin/settings/signatories/save', ['signatories' => $rows])->assertOk();

        $this->assertSame('مدير المعهد بالإنابة', $this->certificate($kind)['signatories']['approved_by']['title']);
    }

    // ------------------------------------------------------------------
    // The official stamp is gone from the whole system
    // ------------------------------------------------------------------

    #[DataProvider('certificateKinds')]
    public function test_no_document_payload_carries_a_stamp(string $kind, string $code): void
    {
        $cert = $this->certificate($kind);

        $this->assertArrayNotHasKey('stamp_url', $cert);
        foreach ($cert['signatories'] as $slot) {
            $this->assertArrayNotHasKey('stamp_url', $slot);
            $this->assertArrayNotHasKey('show_stamp', $slot);
        }

        $report = $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/confidential-report")->assertOk();
        $this->assertArrayNotHasKey('stamp_url', $report->json('report.header'));
    }

    public function test_the_institute_profile_has_no_stamp_and_an_upload_is_ignored(): void
    {
        $this->assertArrayNotHasKey('stamp_url', AdminSettingsService::getInstituteProfile());

        $this->actingAs($this->admin)->post('/api/v1/admin/settings/institute-profile', [
            'state_name' => 'دولة ليبيا', 'supervising_body' => 'الهيئة', 'supervising_department' => 'الإدارة', 'institute_name' => 'المعهد',
            'stamp' => \Illuminate\Http\UploadedFile::fake()->create('stamp.png', 5, 'image/png'),
        ], ['Accept' => 'application/json'])->assertSuccessful();

        $this->assertNull(\App\Models\AdministrativeSetting::where('key', 'stamp_url')->first());
        $this->assertArrayNotHasKey('stamp_url', AdminSettingsService::getInstituteProfile());
    }

    public function test_the_signatory_directory_has_no_stamp_switch(): void
    {
        $this->assertFalse(Schema::hasColumn('official_document_signatories', 'show_stamp'));

        $this->actingAs($this->admin)->postJson('/api/v1/admin/settings/signatories/save', ['signatories' => [[
            'document_code' => 'enrollment_cert', 'slot_key' => 'approved_by', 'slot_label' => 'يعتمد', 'show_stamp' => true,
        ]]])->assertOk();

        $this->assertArrayNotHasKey('show_stamp', OfficialDocumentSignatory::where('slot_key', 'approved_by')->first()->getAttributes());
    }

    public function test_the_migration_removes_a_saved_stamp_and_its_file_and_is_repeatable(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('branding/stamp.png', 'png');
        \App\Models\AdministrativeSetting::create([
            'key' => 'stamp_url', 'value' => '/storage/branding/stamp.png', 'label' => 'الختم', 'type' => 'image', 'setting_group' => 'branding',
        ]);

        $migration = require database_path('migrations/2026_10_27_000002_remove_official_stamp.php');
        $migration->up();
        $migration->up();

        $this->assertNull(\App\Models\AdministrativeSetting::where('key', 'stamp_url')->first());
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('branding/stamp.png');
    }

    public function test_the_screens_and_the_source_carry_no_stamp_feature(): void
    {
        $settings = file_get_contents(resource_path('views/dashboard/sections/admin_settings.blade.php'));
        $scripts = file_get_contents(resource_path('views/dashboard/modals/scripts.blade.php'));
        $students = file_get_contents(resource_path('views/dashboard/sections/students.blade.php'));

        foreach ([$settings, $scripts, $students] as $source) {
            $this->assertStringNotContainsString('stamp_url', $source);
            $this->assertStringNotContainsString('stampPreviewUrl', $source);
            $this->assertStringNotContainsString('show_stamp', $source);
        }
        $this->assertStringNotContainsString('handleStampUpload', $settings . $scripts);
        $this->assertStringNotContainsString('Official Stamp', $settings);
    }

    private function placeHolder(string $name, int $branchId): User
    {
        $user = User::factory()->create(['name' => $name, 'branch_id' => $branchId]);
        EmployeePlacement::create([
            'user_id' => $user->id, 'job_position_id' => $this->branchPosition->id, 'branch_id' => $branchId,
            'start_date' => '2026-09-01', 'is_current' => true, 'status' => 'active', 'show_name_on_documents' => true,
        ]);

        return $user;
    }

    public function test_a_branch_position_never_prints_another_branchs_holder(): void
    {
        $other = Branch::create(['name' => 'فرع آخر', 'code' => 'STP-02', 'city' => 'بنغازي', 'branch_status' => 'ACTIVE']);
        $third = Branch::create(['name' => 'فرع ثالث', 'code' => 'STP-03', 'city' => 'مصراتة', 'branch_status' => 'ACTIVE']);
        $this->placeHolder('مسجل الفرع الآخر', $other->id);
        $this->placeHolder('مسجل الفرع الثالث', $third->id);
        $this->slot('enrollment_cert', 'prepared_by', ['job_position_id' => $this->branchPosition->id, 'user_id' => null]);

        $slot = AdminSettingsService::getSignatoriesFor('enrollment_cert', $this->branch->id)['prepared_by'];

        $this->assertSame('', $slot['name']);
        $this->assertSame('مسجل شؤون الطلبة المعتمد', $slot['title']);
    }

    public function test_the_branch_holder_signs_for_their_own_branch(): void
    {
        $other = Branch::create(['name' => 'فرع آخر', 'code' => 'STP-02', 'city' => 'بنغازي', 'branch_status' => 'ACTIVE']);
        $this->placeHolder('مسجل الفرع الآخر', $other->id);
        $this->placeHolder('مسجل هذا الفرع', $this->branch->id);
        $this->slot('enrollment_cert', 'prepared_by', ['job_position_id' => $this->branchPosition->id, 'user_id' => null]);

        $slot = AdminSettingsService::getSignatoriesFor('enrollment_cert', $this->branch->id)['prepared_by'];

        $this->assertSame('مسجل هذا الفرع', $slot['name']);
    }

    public function test_a_position_held_by_one_person_for_the_whole_institute_still_signs_everywhere(): void
    {
        $other = Branch::create(['name' => 'مقر الإدارة', 'code' => 'HQ-01', 'city' => 'طرابلس', 'branch_status' => 'ACTIVE']);
        $this->placeHolder('المدير العام', $other->id);
        $this->slot('enrollment_cert', 'approved_by', ['job_position_id' => $this->branchPosition->id, 'user_id' => null]);

        $slot = AdminSettingsService::getSignatoriesFor('enrollment_cert', $this->branch->id)['approved_by'];

        $this->assertSame('المدير العام', $slot['name']);
    }
}
