<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\DocumentVerification;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\User;
use App\Services\DocumentLedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1) نطاق الأدوار، 2) مفتاح توقيع الوثائق من البيئة حصراً، 3) التحقق بخطوتين الإلزامي لحسابات الإدارة العامة.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $branch = Branch::where('code', '01')->firstOrFail();
        $year = AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::create(['name' => '2026/2027', 'code' => 'AY-SEC', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
        $studyYear = StudyYear::first() ?? StudyYear::create(['name' => 'السنة الأولى', 'level_order' => 1]);
        $department = Department::first() ?? Department::create(['name' => 'الشريعة', 'code' => 'SHR-SEC']);

        $this->student = Student::create([
            'first_name' => 'طالب', 'father_name' => 'أب', 'grandfather_name' => 'جد', 'family_name' => 'أمن',
            'mother_name' => 'أم', 'national_id' => '330000000003', 'birth_date' => '2005-01-01', 'birth_place' => 'طرابلس',
            'gender' => 'MALE', 'nationality' => 'ليبي', 'nationality_category' => 'LIBYAN',
            'phone' => '0913000003', 'guardian_phone' => '0923000003', 'branch_id' => $branch->id,
            'current_study_year_id' => $studyYear->id, 'enrolled_academic_year_id' => $year->id,
            'academic_year_id' => $year->id, 'department_id' => $department->id,
            'academic_status' => 'ENROLLED_ACTIVE', 'study_type' => 'REGULAR', 'is_archived' => false, 'academic_number' => 'ACN-SEC-1',
        ]);
    }

    private function userWithRole(string $roleName, ?Branch $branch = null): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $roleName)->firstOrFail()->id,
            'branch_id' => $branch?->id,
            'must_change_password' => false,
        ]);
    }

    // ------------------------------------------------------------------
    // 1) نطاق الأدوار
    // ------------------------------------------------------------------

    public function test_branches_director_is_global_and_every_branch_role_stays_branch_scoped(): void
    {
        $this->assertSame('GLOBAL_SCOPE', Role::where('name', 'branches_director')->value('scope_type'));

        $branchRoles = Role::all()->filter(fn ($r) => str_starts_with($r->name, 'branch_') || $r->name === 'course_instructor');
        $this->assertGreaterThanOrEqual(6, $branchRoles->count());
        foreach ($branchRoles as $role) {
            $this->assertSame('BRANCH_SCOPE', $role->scope_type, $role->name);
        }
    }

    public function test_the_scope_migration_repairs_a_database_that_still_has_the_old_scopes_and_is_repeatable(): void
    {
        Role::where('name', 'branches_director')->update(['scope_type' => 'BRANCH_SCOPE']);
        Role::where('name', 'branch_registrar')->update(['scope_type' => 'GLOBAL_SCOPE']);
        Role::where('name', 'course_instructor')->update(['scope_type' => 'GLOBAL_SCOPE']);
        $hqBefore = Role::where('name', 'hq_it_office')->value('scope_type');

        $migration = require database_path('migrations/2026_10_27_000001_enforce_role_scopes.php');
        $migration->up();
        $migration->up();

        $this->assertSame('GLOBAL_SCOPE', Role::where('name', 'branches_director')->value('scope_type'));
        $this->assertSame('BRANCH_SCOPE', Role::where('name', 'branch_registrar')->value('scope_type'));
        $this->assertSame('BRANCH_SCOPE', Role::where('name', 'course_instructor')->value('scope_type'));
        $this->assertSame($hqBefore, Role::where('name', 'hq_it_office')->value('scope_type'), 'HQ roles are untouched.');
    }

    public function test_a_branches_director_sees_every_branch_once_a_permission_is_granted_and_nothing_before(): void
    {
        $director = User::where('email', 'branches.director@iiis.sch.ly')->firstOrFail();
        $this->assertTrue($director->hasGlobalAccessScope());
        $this->assertNull($director->branch_id);

        $other = Branch::where('code', '02')->firstOrFail();
        $foreign = Student::create(array_merge($this->student->only([
            'first_name', 'father_name', 'grandfather_name', 'family_name', 'mother_name', 'birth_date', 'birth_place', 'gender',
            'nationality', 'nationality_category', 'current_study_year_id', 'enrolled_academic_year_id', 'academic_year_id',
            'department_id', 'academic_status', 'study_type', 'is_archived',
        ]), [
            'national_id' => '440000000004', 'phone' => '0914000004', 'guardian_phone' => '0924000004',
            'branch_id' => $other->id, 'academic_number' => 'ACN-SEC-2',
        ]));

        // The role carries no permission of its own: scope alone opens nothing.
        $this->actingAs($director)->getJson('/api/v1/students')->assertForbidden();

        $director->directPermissions()->attach(\App\Models\Permission::where('code', 'students.view')->firstOrFail()->id, ['is_granted' => true]);

        $content = $this->actingAs($director->fresh())->getJson('/api/v1/students')->assertOk()->getContent();
        $this->assertStringContainsString($this->student->national_id, $content);
        $this->assertStringContainsString($foreign->national_id, $content);
    }

    // ------------------------------------------------------------------
    // 2) مفتاح توقيع الوثائق
    // ------------------------------------------------------------------

    public function test_signing_stops_with_an_explicit_exception_when_the_key_is_not_defined(): void
    {
        foreach ([null, '', '   '] as $missing) {
            config(['app.document_signature_key' => $missing]);

            try {
                app(DocumentLedgerService::class)->issueDocument('ENROLLMENT_CERTIFICATE', $this->student, [], 'مدير', null);
                $this->fail('Signing must stop when DOCUMENT_SIGNATURE_KEY is missing.');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('DOCUMENT_SIGNATURE_KEY', $e->getMessage());
            }
        }

        $this->assertSame(0, DocumentVerification::count(), 'No document may be registered without a key.');
    }

    public function test_verification_also_refuses_to_run_without_the_key(): void
    {
        config(['app.document_signature_key' => 'a-key-that-exists']);
        $doc = app(DocumentLedgerService::class)->issueDocument('ENROLLMENT_CERTIFICATE', $this->student, [], 'مدير', null);

        config(['app.document_signature_key' => null]);
        $this->expectException(\RuntimeException::class);
        app(DocumentLedgerService::class)->verifyDocument($doc->document_uuid);
    }

    public function test_the_app_key_is_never_used_as_a_silent_fallback(): void
    {
        $this->assertNotEmpty(config('app.key'));
        config(['app.document_signature_key' => null]);

        $this->expectException(\RuntimeException::class);
        app(DocumentLedgerService::class)->issueDocument('ENROLLMENT_CERTIFICATE', $this->student, [], 'مدير', null);
    }

    public function test_a_document_signed_with_the_environment_key_verifies_and_a_different_key_flags_it_as_tampered(): void
    {
        config(['app.document_signature_key' => 'environment-key-one']);
        $service = app(DocumentLedgerService::class);
        $doc = $service->issueDocument('ENROLLMENT_CERTIFICATE', $this->student, [], 'مدير', null);

        $this->assertTrue($service->verifyDocument($doc->document_uuid)['is_valid']);

        config(['app.document_signature_key' => 'environment-key-two']);
        $result = $service->verifyDocument($doc->document_uuid);
        $this->assertFalse($result['is_valid']);
        $this->assertSame('TAMPERED', $result['status']);
    }

    public function test_the_certificate_endpoint_fails_closed_and_registers_nothing_without_the_key(): void
    {
        config(['app.document_signature_key' => null]);
        $admin = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();

        $response = $this->actingAs($admin)->getJson("/api/v1/students/{$this->student->id}/certificates/enrollment");

        $this->assertGreaterThanOrEqual(500, $response->getStatusCode());
        $this->assertSame(0, DocumentVerification::count());
    }

    public function test_the_source_code_carries_no_hardcoded_signing_key(): void
    {
        $source = file_get_contents(app_path('Services/DocumentLedgerService.php'));

        $this->assertStringNotContainsString('iiis-secure-hmac-key', $source);
        $this->assertStringNotContainsString('document_signing_key', $source);
        $this->assertStringContainsString("config('app.document_signature_key')", $source);
        $this->assertStringContainsString('DOCUMENT_SIGNATURE_KEY', file_get_contents(base_path('.env.example')));
        $this->assertStringContainsString("env('DOCUMENT_SIGNATURE_KEY')", file_get_contents(config_path('app.php')));
    }

    // ------------------------------------------------------------------
    // 3) التحقق بخطوتين (معطّل افتراضياً، ويُفعَّل بمتغيرات البيئة)
    // ------------------------------------------------------------------

    private function enforceMfa(bool $generalSwitch = false): void
    {
        config(['auth.mfa_enforce_hq' => true, 'auth.mfa_enabled' => $generalSwitch]);
    }

    public function test_head_office_accounts_must_use_mfa_when_their_switch_is_on_even_if_the_general_switch_is_off(): void
    {
        $this->enforceMfa(false);

        $roles = Role::all()->filter(fn ($r) => str_starts_with($r->name, 'hq_') || $r->name === 'super_admin');
        $this->assertGreaterThan(10, $roles->count());

        foreach ($roles as $role) {
            $user = $this->userWithRole($role->name);
            $this->assertTrue($user->requiresMfa(), $role->name . ' must require MFA.');

            $this->actingAs($user)->getJson('/api/v1/students')
                ->assertStatus(403)
                ->assertJsonPath('code', 'MFA_SETUP_REQUIRED');
        }
    }

    public function test_branch_accounts_are_not_forced_into_mfa_by_the_head_office_rule(): void
    {
        $this->enforceMfa(false);
        $branch = Branch::where('code', '01')->firstOrFail();

        foreach (['branch_manager', 'branch_registrar', 'branch_exams_officer', 'course_instructor'] as $roleName) {
            $this->assertFalse($this->userWithRole($roleName, $branch)->requiresMfa(), $roleName);
        }
        $this->actingAs($this->userWithRole('branch_manager', $branch))->getJson('/api/v1/students')->assertOk();
    }

    public function test_the_general_switch_widens_the_rule_to_the_other_global_scope_roles(): void
    {
        $director = $this->userWithRole('branches_director');

        $this->enforceMfa(false);
        $this->assertFalse($director->requiresMfa(), 'branches_director is not head office; the general switch is off.');

        $this->enforceMfa(true);
        $this->assertTrue($director->requiresMfa());
        $this->assertFalse($this->userWithRole('branch_manager', Branch::where('code', '01')->firstOrFail())->requiresMfa());
    }

    public function test_a_confirmed_account_must_pass_the_challenge_in_every_session(): void
    {
        $this->enforceMfa(false);
        $admin = $this->userWithRole('super_admin');
        $admin->forceFill(['two_factor_enabled' => true, 'two_factor_confirmed_at' => Carbon::now()])->save();

        $this->actingAs($admin)->getJson('/api/v1/students')
            ->assertStatus(403)
            ->assertJsonPath('code', 'MFA_CHALLENGE_REQUIRED');

        $this->actingAs($admin)->withSession(['mfa_verified' => true])->getJson('/api/v1/students')->assertOk();
    }

    public function test_the_web_pages_redirect_an_unenrolled_head_office_account_to_mfa_setup(): void
    {
        $this->enforceMfa(false);

        $this->actingAs($this->userWithRole('hq_student_affairs'))->get('/')->assertRedirect(route('mfa.setup'));
    }

    public function test_two_step_verification_is_off_by_default_and_each_switch_still_works(): void
    {
        $this->assertMatchesRegularExpression(
            "/'mfa_enforce_hq'\s*=>\s*env\('MFA_ENFORCE_HQ',\s*false\)/",
            file_get_contents(config_path('auth.php'))
        );
        $env = file_get_contents(base_path('.env.example'));
        $this->assertStringContainsString('MFA_ENFORCE_HQ=false', $env);
        $this->assertStringContainsString('MFA_ENABLED=false', $env);

        // With both switches off no account is sent to the setup screen.
        config(['auth.mfa_enforce_hq' => false, 'auth.mfa_enabled' => false]);
        foreach (['super_admin', 'hq_student_affairs', 'branches_director'] as $roleName) {
            $this->assertFalse($this->userWithRole($roleName)->requiresMfa(), $roleName);
        }
        $this->actingAs($this->userWithRole('super_admin'))->getJson('/api/v1/students')->assertOk();
    }

    // ------------------------------------------------------------------
    // 5) فصل الكاش والجلسات والطوابير
    // ------------------------------------------------------------------

    public function test_redis_profile_keeps_cache_sessions_and_queues_in_separate_databases(): void
    {
        $redis = config('database.redis');

        $databases = [
            'cache' => $redis['cache']['database'] ?? null,
            'session' => $redis['session']['database'] ?? null,
            'queue' => $redis['queue']['database'] ?? null,
        ];
        $this->assertCount(3, array_unique($databases), 'cache, session and queue must not share one Redis database: ' . json_encode($databases));
        $this->assertNotSame($redis['default']['database'], $redis['queue']['database']);
        $this->assertSame('queue', config('queue.connections.redis.connection'));
    }

    public function test_default_profile_is_unchanged_and_documented(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('SESSION_DRIVER=database', $env);
        $this->assertStringContainsString('QUEUE_CONNECTION=database', $env);
        $this->assertStringContainsString('CACHE_STORE=database', $env);
        $this->assertStringContainsString('REDIS_SESSION_DB=2', $env);
    }
}
