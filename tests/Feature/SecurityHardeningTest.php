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
    // 3) التحقق بخطوتين أُلغي من النظام بالكامل
    // ------------------------------------------------------------------

    public function test_two_step_verification_no_longer_exists_in_the_routes_or_the_middleware(): void
    {
        foreach (['mfa.setup', 'mfa.confirm', 'mfa.challenge', 'mfa.verify'] as $name) {
            $this->assertFalse(\Illuminate\Support\Facades\Route::has($name), "$name must be gone");
        }
        foreach (['/mfa/setup', '/mfa/challenge'] as $path) {
            $this->actingAs($this->userWithRole('super_admin'))->get($path)->assertNotFound();
        }

        $this->assertArrayNotHasKey('enforce.mfa', app('router')->getMiddleware());
        $this->assertFalse(class_exists(\App\Services\TotpService::class));
        $this->assertFalse(method_exists(User::class, 'requiresMfa'));
        $this->assertNull(config('auth.mfa_enabled'));
        $this->assertNull(config('auth.mfa_enforce_hq'));
    }

    public function test_the_users_table_no_longer_carries_two_factor_columns_and_the_migration_is_repeatable(): void
    {
        foreach (['two_factor_secret', 'two_factor_enabled', 'two_factor_confirmed_at', 'two_factor_recovery_codes'] as $column) {
            $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('users', $column), $column);
        }

        $migration = require database_path('migrations/2026_10_27_000003_remove_two_factor_authentication.php');
        $migration->up();
        $migration->up();

        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('users', 'two_factor_enabled'));
    }

    public function test_every_role_signs_in_straight_to_the_dashboard_and_the_api(): void
    {
        foreach (['super_admin', 'hq_student_affairs', 'branches_director'] as $roleName) {
            $user = $this->userWithRole($roleName);
            $this->actingAs($user)->get('/')->assertOk();
            $this->actingAs($user)->getJson('/api/v1/users/' . $user->id . '/permissions')->assertStatus(in_array($roleName, ['super_admin']) ? 200 : 403);
        }
    }

    public function test_a_real_login_never_redirects_to_a_challenge(): void
    {
        $user = $this->userWithRole('super_admin');
        $user->forceFill(['password' => \Illuminate\Support\Facades\Hash::make('Plain-Login-2026!')])->save();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'Plain-Login-2026!']);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
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
