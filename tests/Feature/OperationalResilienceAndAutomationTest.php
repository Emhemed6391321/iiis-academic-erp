<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\BranchContract;
use App\Models\BranchRequest;
use App\Models\Department;
use App\Models\DocumentVerification;
use App\Models\OfflineSyncLog;
use App\Models\Property;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudyYear;
use App\Models\SystemAuditTrail;
use App\Models\User;
use App\Services\DocumentLedgerService;
use App\Services\MetricsCacheService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationalResilienceAndAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Branch $branch;
    protected AcademicYear $academicYear;
    protected StudyYear $studyYear;
    protected Department $department;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR-TEST-HQ'],
            ['name' => 'فرع الاختبار التجريبي', 'city' => 'طرابلس', 'is_active' => true]
        );

        $this->academicYear = AcademicYear::firstOrCreate(
            ['code' => '2026-2027'],
            [
                'name' => 'العام الدراسي 2026-2027',
                'start_date' => '2026-09-01',
                'end_date' => '2027-06-30',
                'is_current' => true,
            ]
        );

        $this->studyYear = StudyYear::firstOrCreate(
            ['name' => 'السنة الأولى'],
            ['level_order' => 1]
        );

        $this->department = Department::firstOrCreate(
            ['code' => 'SHARIA'],
            ['name' => 'قسم الشريعة والدراسات الإسلامية', 'is_active' => true]
        );

        $roleHq = \App\Models\Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'مدير النظام', 'scope_type' => 'GLOBAL_SCOPE']
        );

        $this->superAdmin = User::firstOrCreate(
            ['username' => 'sre_admin_test'],
            [
                'name' => 'مهندس العمليات والموثوقية',
                'email' => 'sre@iiis.sch.ly',
                'password' => bcrypt('password123'),
                'role_id' => $roleHq->id,
                'is_active' => true,
            ]
        );

        $this->student = Student::firstOrCreate(
            ['admission_number' => 'STD-SRE-1001'],
            [
                'first_name' => 'أسامة',
                'father_name' => 'علي',
                'grandfather_name' => 'محمد',
                'family_name' => 'الورفلي',
                'mother_name' => 'فاطمة',
                'birth_place' => 'طرابلس',
                'guardian_phone' => '0912345679',
                'study_type' => 'REGULAR',
                'branch_id' => $this->branch->id,
                'enrolled_academic_year_id' => $this->academicYear->id,
                'current_study_year_id' => $this->studyYear->id,
                'department_id' => $this->department->id,
                'academic_status' => 'ENROLLED_ACTIVE',
                'national_id' => '119900123456',
                'phone' => '0912345678',
                'gender' => 'MALE',
                'birth_date' => '2005-01-01',
            ]
        );
    }

    /**
     * Pillar 1: Offline Attendance Batch Sync with Idempotency & Conflict Resolution
     */
    public function test_offline_attendance_batch_sync_is_idempotent_and_resolves_conflicts(): void
    {
        $nonce = 'NONCE_' . Str::uuid();
        $recordDate = Carbon::today()->format('Y-m-d');

        $payload = [
            'batch_id' => (string) Str::uuid(),
            'device_uuid' => 'DEV-TERMINAL-01',
            'branch_id' => $this->branch->id,
            'records' => [
                [
                    'sync_nonce' => $nonce,
                    'student_id' => $this->student->id,
                    'record_date' => $recordDate,
                    'check_in_time' => '08:15:00',
                    'status' => 'present',
                    'client_recorded_at' => Carbon::now()->toIso8601String(),
                ]
            ]
        ];

        // 1. Initial sync
        $response1 = $this->actingAs($this->superAdmin)
            ->postJson('/api/v1/attendance/batch-sync', $payload);

        $response1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.synced', 1)
            ->assertJsonPath('data.duplicates', 0);

        $this->assertDatabaseHas('student_attendance', [
            'sync_nonce' => $nonce,
            'student_id' => $this->student->id,
            'is_offline_sync' => true,
        ]);

        // 2. Transmit the EXACT same batch again (simulating network retry)
        $response2 = $this->actingAs($this->superAdmin)
            ->postJson('/api/v1/attendance/batch-sync', $payload);

        $response2->assertOk()
            ->assertJsonPath('data.synced', 0)
            ->assertJsonPath('data.duplicates', 1);

        // Ensure no duplicate rows were inserted
        $this->assertEquals(1, StudentAttendance::where('sync_nonce', $nonce)->count());

        // 3. Merging departure into existing check-in
        $departureNonce = 'NONCE_' . Str::uuid();
        $departurePayload = [
            'batch_id' => (string) Str::uuid(),
            'device_uuid' => 'DEV-TERMINAL-01',
            'branch_id' => $this->branch->id,
            'records' => [
                [
                    'sync_nonce' => $departureNonce,
                    'student_id' => $this->student->id,
                    'record_date' => $recordDate,
                    'check_out_time' => '13:45:00',
                    'departure_status' => 'normal',
                ]
            ]
        ];

        $response3 = $this->actingAs($this->superAdmin)
            ->postJson('/api/v1/attendance/batch-sync', $departurePayload);

        $response3->assertOk()
            ->assertJsonPath('data.synced', 1);

        $updatedAttendance = StudentAttendance::withoutGlobalScopes()
            ->where('student_id', $this->student->id)
            ->whereDate('record_date', $recordDate)
            ->first();

        $this->assertNotNull($updatedAttendance);
        $this->assertEquals('08:15:00', $updatedAttendance->check_in_time);
        $this->assertEquals('13:45:00', $updatedAttendance->check_out_time);

        // Verify offline sync log exists
        $this->assertDatabaseHas('offline_sync_logs', [
            'client_device_info' => 'DEV-TERMINAL-01',
            'sync_status' => 'COMPLETED',
        ]);
    }

    /**
     * Pillar 2: Scheduled Automation Commands
     */
    public function test_operational_automation_commands(): void
    {
        // 1. Contract Expiration Command
        $property = Property::firstOrCreate(
            ['property_code' => 'PROP-TEST-SRE'],
            [
                'property_number' => 'PROP-NUM-SRE-01',
                'name' => 'مبنى فرع طرابلس النموذجي',
                'branch_id' => $this->branch->id,
                'ownership_type' => 'RENTED',
                'city' => 'طرابلس',
                'is_active' => true,
            ]
        );

        $expiringContract = BranchContract::create([
            'contract_number' => 'CONT-SRE-30D',
            'branch_id' => $this->branch->id,
            'property_id' => $property->id,
            'contract_type' => 'LEASE',
            'title' => 'عقد إيجار مقر طرابلس',
            'contractor_name' => 'شركة البناء الحديث',
            'contractor_phone' => '0912223344',
            'duration_months' => 12,
            'total_value' => 60000,
            'status' => 'ACTIVE',
            'start_date' => Carbon::today()->subMonths(11)->format('Y-m-d'),
            'end_date' => Carbon::today()->addDays(20)->format('Y-m-d'), // expires in 20 days
            'rent_amount' => 5000,
        ]);

        $exitCode1 = Artisan::call('contracts:monitor-expirations', ['--days' => 30]);
        $this->assertEquals(0, $exitCode1);

        $this->assertDatabaseHas('branch_requests', [
            'ticket_number' => 'TICK-EXP-' . $expiringContract->id,
            'branch_id' => $this->branch->id,
            'status' => 'NEW',
        ]);

        // 2. Absence Thresholds Command (Smart Warnings & Retroactive Lock)
        // Record 7 days of absence for student (> 10% threshold)
        for ($i = 1; $i <= 7; $i++) {
            StudentAttendance::create([
                'student_id' => $this->student->id,
                'branch_id' => $this->branch->id,
                'academic_year_id' => $this->academicYear->id,
                'record_date' => Carbon::today()->subDays($i)->format('Y-m-d'),
                'status' => 'absent',
            ]);
        }

        $exitCode2 = Artisan::call('attendance:monitor-absence-thresholds', ['--min-days' => 60]);
        $this->assertEquals(0, $exitCode2);

        $this->assertDatabaseHas('attendance_warning_notices', [
            'student_id' => $this->student->id,
            'warning_level' => 'SECOND_WARNING',
        ]);

        // Verify retroactive modification lock was applied
        $lockedRecords = StudentAttendance::where('student_id', $this->student->id)
            ->where('status', 'absent')
            ->whereNotNull('modification_reason')
            ->count();

        $this->assertGreaterThanOrEqual(7, $lockedRecords);

        // 3. Branch Lease Compliance Command (Grace period breach)
        $overdueContract = BranchContract::create([
            'contract_number' => 'CONT-SRE-OVERDUE',
            'branch_id' => $this->branch->id,
            'property_id' => $property->id,
            'contract_type' => 'LEASE',
            'title' => 'عقد مقر متجاوز للمهلة',
            'contractor_name' => 'شركة البناء الحديث',
            'contractor_phone' => '0912223344',
            'duration_months' => 12,
            'total_value' => 60000,
            'status' => 'ACTIVE',
            'start_date' => Carbon::today()->subYears(2)->format('Y-m-d'),
            'end_date' => Carbon::today()->subDays(40)->format('Y-m-d'), // expired 40 days ago
            'grace_period_days' => 15, // grace period exceeded by 25 days
        ]);

        $exitCode3 = Artisan::call('branches:audit-contract-compliance');
        $this->assertEquals(0, $exitCode3);

        $this->assertDatabaseHas('branch_requests', [
            'ticket_number' => 'CRIT-LEASE-' . $overdueContract->id,
            'priority' => 'URGENT',
        ]);
    }

    /**
     * Pillar 3: Cryptographic Document Ledger & Public Verification
     */
    public function test_document_ledger_issuance_and_public_verification(): void
    {
        $ledgerService = app(DocumentLedgerService::class);

        // Issue document
        $doc = $ledgerService->issueDocument(
            'ENROLLMENT_CERTIFICATE',
            $this->student,
            ['study_year' => 'السنة الأولى'],
            'مدير عام المعهد'
        );

        $this->assertNotEmpty($doc->document_uuid);
        $this->assertNotEmpty($doc->hash_signature);

        // 1. Verify via public Web URL (returns Blade view)
        $webResponse = $this->get('/verify/' . $doc->document_uuid);
        $webResponse->assertOk()
            ->assertSee('وثيقة أصلية ومعتمدة رسمياً')
            ->assertSee($ledgerService->maskName($this->student->full_name))
            ->assertDontSee($this->student->national_id) // Strict privacy boundary
            ->assertDontSee($this->student->phone);

        // 2. Verify via API endpoint (returns JSON)
        $apiResponse = $this->getJson('/api/v1/verify/' . $doc->document_uuid);
        $apiResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.document.student_name', $ledgerService->maskName($this->student->full_name));

        // 3. Revoke document and verify updated public status
        $ledgerService->revokeDocument($doc->document_uuid, 'إلغاء لغرض تجديد القيد');

        $revokedResponse = $this->get('/verify/' . $doc->document_uuid);
        $revokedResponse->assertOk()
            ->assertSee('وثيقة ملغاة رسمياً');

        // 4. Non-existent UUID
        $notFoundResponse = $this->get('/verify/' . Str::uuid());
        $notFoundResponse->assertOk()
            ->assertSee('الوثيقة غير موجودة أو غير معتمدة');
    }

    /**
     * Pillar 4: Aggregated Metrics Caching & Automated Event Invalidation
     */
    public function test_metrics_caching_and_event_invalidation(): void
    {
        $cacheService = app(MetricsCacheService::class);
        $cacheService->invalidateAll();

        // 1. First fetch computes and stores in cache
        $data1 = $cacheService->getHQSummary();
        $this->assertArrayHasKey('kpis', $data1);
        $this->assertArrayHasKey('cached_at', $data1);

        // 2. API endpoint returns cached summary quickly
        $response = $this->actingAs($this->superAdmin)->getJson('/api/v1/hq/dashboard');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('kpis.total_students', $data1['kpis']['total_students']);

        // 3. Creating a new student automatically invalidates cache via Eloquent Model Event
        $initialTotal = $data1['kpis']['total_students'];

        Student::create([
            'admission_number' => 'STD-NEW-CACHE-TEST',
            'first_name' => 'طارق',
            'father_name' => 'سالم',
            'grandfather_name' => 'أحمد',
            'family_name' => 'المهدي',
            'mother_name' => 'مريم',
            'birth_place' => 'طرابلس',
            'guardian_phone' => '0919999999',
            'study_type' => 'REGULAR',
            'branch_id' => $this->branch->id,
            'enrolled_academic_year_id' => $this->academicYear->id,
            'current_study_year_id' => $this->studyYear->id,
            'department_id' => $this->department->id,
            'academic_status' => 'ENROLLED_ACTIVE',
            'national_id' => '119900999888',
            'phone' => '0919999998',
            'gender' => 'MALE',
            'birth_date' => '2005-02-02',
        ]);

        // Check new summary reflects updated student count
        $data2 = $cacheService->getHQSummary();
        $this->assertEquals($initialTotal + 1, $data2['kpis']['total_students']);
    }
}
