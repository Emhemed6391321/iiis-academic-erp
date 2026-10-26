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
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The official stamp is linked to the signature slots in the central settings: it prints at an active slot whose
 * stamp switch is on, and nowhere else. Disabling, stopping or editing a slot changes the printed document at once.
 */
class SignatoryStampsTest extends TestCase
{
    use RefreshDatabase;

    private const STAMP_URL = '/storage/branding/stamp.png';

    private User $admin;
    private Branch $branch;
    private Student $student;
    private JobPosition $branchPosition;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put('branding/stamp.png', 'png');
        AdminSettingsService::setSetting('stamp_url', self::STAMP_URL, 'branding', 'الختم الرسمي للمعهد', 'image');

        $superRole = Role::create(['name' => 'super_admin', 'display_name' => 'المدير العام', 'scope_type' => 'GLOBAL_SCOPE']);
        $this->admin = User::factory()->create(['role_id' => $superRole->id]);

        $registrarRole = Role::create(['name' => 'branch_registrar', 'display_name' => 'مسجل شؤون الطلبة', 'scope_type' => 'BRANCH_SCOPE']);
        AdminSettingsService::syncRolePositions();
        $this->branchPosition = JobPosition::where('role_id', $registrarRole->id)->firstOrFail();

        $this->branch = Branch::create(['name' => 'فرع الختم', 'code' => 'STP-01', 'city' => 'طرابلس', 'branch_status' => 'ACTIVE']);

        foreach (['enrollment_cert', 'conduct_cert'] as $code) {
            $this->slot($code, 'prepared_by', ['show_stamp' => false, 'custom_title_override' => 'مسجل شؤون الطلبة المعتمد']);
            $this->slot($code, 'approved_by', ['show_stamp' => true, 'custom_title_override' => 'مدير عام المعهد']);
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
    public function test_stamp_prints_at_the_approving_slot_only(string $kind, string $code): void
    {
        $cert = $this->certificate($kind);

        $this->assertSame(self::STAMP_URL, $cert['signatories']['approved_by']['stamp_url']);
        $this->assertSame('', $cert['signatories']['prepared_by']['stamp_url']);
        $this->assertSame(self::STAMP_URL, $cert['stamp_url']);
    }

    #[DataProvider('certificateKinds')]
    public function test_stamp_follows_the_slot_switch(string $kind, string $code): void
    {
        $this->slot($code, 'approved_by', ['show_stamp' => false]);
        $this->slot($code, 'prepared_by', ['show_stamp' => true]);
        AdminSettingsService::clearCache();

        $cert = $this->certificate($kind);

        $this->assertSame('', $cert['signatories']['approved_by']['stamp_url']);
        $this->assertSame(self::STAMP_URL, $cert['signatories']['prepared_by']['stamp_url']);
        $this->assertSame(self::STAMP_URL, $cert['stamp_url']);
    }

    #[DataProvider('certificateKinds')]
    public function test_no_stamp_is_printed_when_every_stamp_switch_is_off(string $kind, string $code): void
    {
        OfficialDocumentSignatory::where('document_code', $code)->update(['show_stamp' => false]);

        $cert = $this->certificate($kind);

        $this->assertSame('', $cert['stamp_url']);
        $this->assertSame('', $cert['signatories']['approved_by']['stamp_url']);
    }

    #[DataProvider('certificateKinds')]
    public function test_a_stopped_slot_drops_its_signature_and_its_stamp(string $kind, string $code): void
    {
        $this->slot($code, 'approved_by', ['is_active' => false]);

        $cert = $this->certificate($kind);

        $this->assertArrayNotHasKey('approved_by', $cert['signatories']);
        $this->assertSame('', $cert['stamp_url']);
        $this->assertArrayHasKey('prepared_by', $cert['signatories']);
    }

    #[DataProvider('certificateKinds')]
    public function test_no_stamp_is_printed_when_none_was_uploaded(string $kind, string $code): void
    {
        AdminSettingsService::setSetting('stamp_url', '', 'branding');

        $cert = $this->certificate($kind);

        $this->assertSame('', $cert['stamp_url']);
        $this->assertSame('', $cert['signatories']['approved_by']['stamp_url']);
    }

    #[DataProvider('certificateKinds')]
    public function test_a_missing_stamp_file_is_never_printed_as_a_broken_image(string $kind, string $code): void
    {
        Storage::disk('public')->delete('branding/stamp.png');

        $cert = $this->certificate($kind);

        $this->assertSame('', $cert['stamp_url']);
    }

    #[DataProvider('certificateKinds')]
    public function test_replacing_the_stamp_takes_effect_on_the_next_print(string $kind, string $code): void
    {
        $this->certificate($kind);

        Storage::disk('public')->put('branding/new-stamp.png', 'png');
        AdminSettingsService::setSetting('stamp_url', '/storage/branding/new-stamp.png', 'branding');

        $this->assertSame('/storage/branding/new-stamp.png', $this->certificate($kind)['stamp_url']);
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
            'show_stamp' => $s->slot_key === 'prepared_by',
        ])->values()->all();

        $this->actingAs($this->admin)->postJson('/api/v1/admin/settings/signatories/save', ['signatories' => $rows])
            ->assertOk();

        $cert = $this->certificate($kind);

        $this->assertSame('مدير المعهد بالإنابة', $cert['signatories']['approved_by']['title']);
        $this->assertSame('', $cert['signatories']['approved_by']['stamp_url']);
        $this->assertSame(self::STAMP_URL, $cert['signatories']['prepared_by']['stamp_url']);
    }

    public function test_saving_without_the_stamp_field_keeps_each_slots_stamp_switch(): void
    {
        $this->actingAs($this->admin)->postJson('/api/v1/admin/settings/signatories/save', ['signatories' => [[
            'document_code' => 'enrollment_cert',
            'slot_key' => 'approved_by',
            'slot_label' => 'يعتمد',
            'custom_title_override' => 'مدير عام المعهد',
        ], [
            'document_code' => 'enrollment_cert',
            'slot_key' => 'issued_by',
            'slot_label' => 'المستخرج',
        ]]])->assertOk();

        $this->assertTrue(OfficialDocumentSignatory::where('document_code', 'enrollment_cert')->where('slot_key', 'approved_by')->value('show_stamp'));
        $this->assertFalse(OfficialDocumentSignatory::where('document_code', 'enrollment_cert')->where('slot_key', 'issued_by')->value('show_stamp'));
    }

    public function test_confidential_report_uses_the_same_stamp_rule(): void
    {
        $this->slot('secret_report', 'approved_by', ['show_stamp' => true]);
        AdminSettingsService::clearCache();

        $response = $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/confidential-report");
        $response->assertOk();
        $this->assertSame(self::STAMP_URL, $response->json('report.header.stamp_url'));

        $this->slot('secret_report', 'approved_by', ['show_stamp' => false]);
        $response = $this->actingAs($this->admin)->getJson("/api/v1/students/{$this->student->id}/confidential-report");
        $this->assertSame('', $response->json('report.header.stamp_url'));
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
