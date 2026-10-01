<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\AcademicYear;
use App\Models\StudyYear;
use App\Models\Department;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentTransfer;
use App\Models\EnrollmentStatusRequest;
use App\Models\AttendanceWarningNotice;
use App\Services\AbsenceCalculationEngineService;
use App\Services\StudentStateMachineService;
use Carbon\Carbon;

class BusinessLogicPhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    protected User $hqAdmin;
    protected User $branchUser1;
    protected User $branchUser2;
    protected Branch $branch1;
    protected Branch $branch2;
    protected AcademicYear $year;
    protected Department $department;
    protected StudyYear $studyYear;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('vault');
        Storage::fake('public');

        $globalRole = Role::firstOrCreate(['name' => 'super_admin'], [
            'display_name' => 'المدير العام',
            'scope_type'   => 'GLOBAL_SCOPE',
        ]);

        $branchRole = Role::firstOrCreate(['name' => 'branch_registrar'], [
            'display_name' => 'مسجل الفرع',
            'scope_type'   => 'BRANCH_SCOPE',
        ]);

        $this->branch1 = Branch::create([
            'code'    => 'TRIPOLI',
            'name'    => 'فرع طرابلس المركزي',
            'city'    => 'طرابلس',
            'address' => 'طريق الشط',
        ]);

        $this->branch2 = Branch::create([
            'code'    => 'BENGHAZI',
            'name'    => 'فرع بنغازي',
            'city'    => 'بنغازي',
            'address' => 'الفويهات',
        ]);

        $this->year = AcademicYear::create([
            'code'       => '2026-2027',
            'name'       => 'العام الدراسي 2026/2027',
            'start_date' => '2026-09-01',
            'end_date'   => '2027-06-30',
            'is_current' => true,
        ]);

        $this->department = Department::create([
            'code' => 'ISLAMIC_STUDIES',
            'name' => 'شعبة الدراسات الإسلامية',
        ]);

        $this->studyYear = StudyYear::create([
            'name'        => 'السنة الأولى',
            'level_order' => 1,
        ]);

        $this->hqAdmin = User::create([
            'name'        => 'مسؤول الإدارة العامة',
            'email'       => 'hq@iiis.sch.ly',
            'password'    => bcrypt('ValidPass123#'),
            'role_id'     => $globalRole->id,
            'national_id' => '119800000001',
            'phone'       => '091-0000001',
        ]);

        $this->branchUser1 = User::create([
            'name'        => 'مسجل فرع طرابلس',
            'email'       => 'tripoli_reg@iiis.sch.ly',
            'password'    => bcrypt('ValidPass123#'),
            'role_id'     => $branchRole->id,
            'branch_id'   => $this->branch1->id,
            'national_id' => '119800000002',
            'phone'       => '091-0000002',
        ]);

        $this->branchUser2 = User::create([
            'name'        => 'مسجل فرع بنغازي',
            'email'       => 'benghazi_reg@iiis.sch.ly',
            'password'    => bcrypt('ValidPass123#'),
            'role_id'     => $branchRole->id,
            'branch_id'   => $this->branch2->id,
            'national_id' => '119800000003',
            'phone'       => '091-0000003',
        ]);
    }

    protected function createStudent(array $overrides = []): Student
    {
        return Student::create(array_merge([
            'academic_number'           => 'STU-' . rand(10000, 99999),
            'national_id'               => '1200' . rand(10000000, 99999999),
            'branch_id'                 => $this->branch1->id,
            'department_id'             => $this->department->id,
            'current_study_year_id'     => $this->studyYear->id,
            'enrolled_academic_year_id' => $this->year->id,
            'first_name'                => 'محمد',
            'father_name'               => 'سالم',
            'grandfather_name'          => 'فرج',
            'family_name'               => 'الورفلي',
            'mother_name'               => 'مريم',
            'gender'                    => 'MALE',
            'birth_date'                => '2008-05-15',
            'birth_place'               => 'طرابلس',
            'phone'                     => '091-5555555',
            'guardian_phone'            => '092-6666666',
            'study_type'                => 'REGULAR',
            'academic_status'           => 'ENROLLED_ACTIVE',
        ], $overrides));
    }

    // =========================================================================
    // 1. Absence & Deprivation Engine Tests (Option B)
    // =========================================================================

    public function test_option_b_absence_percentage_calculated_on_semester_instructional_days(): void
    {
        $student = $this->createStudent();
        $engine = app(AbsenceCalculationEngineService::class);

        // 60 standard semester days, record 12 unexcused days -> 12 / 60 = 20% (EXPULSION_NOTICE)
        for ($i = 1; $i <= 12; $i++) {
            StudentAttendance::create([
                'student_id'       => $student->id,
                'branch_id'        => $this->branch1->id,
                'academic_year_id' => $this->year->id,
                'record_date'      => Carbon::parse('2026-09-01')->addDays($i)->format('Y-m-d'),
                'status'           => 'ABSENT_UNEXCUSED',
                'recorded_by'      => $this->branchUser1->id,
            ]);
        }

        $metrics = $engine->evaluateStudentAbsence($student, $this->year->id);

        $this->assertEquals(60, $metrics['total_semester_days']);
        $this->assertEquals(12, $metrics['unexcused_days']);
        $this->assertEquals(20.0, $metrics['absence_percentage']);
        $this->assertEquals('EXPULSION_NOTICE', $metrics['warning_level']);
        $this->assertTrue($metrics['is_deprived']);
    }

    public function test_threshold_triggers_warning_levels_correctly(): void
    {
        $student = $this->createStudent();
        $engine = app(AbsenceCalculationEngineService::class);

        // 3 unexcused days out of 60 = 5.0% -> FIRST_WARNING
        for ($i = 1; $i <= 3; $i++) {
            StudentAttendance::create([
                'student_id'       => $student->id,
                'branch_id'        => $this->branch1->id,
                'academic_year_id' => $this->year->id,
                'record_date'      => Carbon::parse('2026-09-01')->addDays($i)->format('Y-m-d'),
                'status'           => 'ABSENT',
                'recorded_by'      => $this->branchUser1->id,
            ]);
        }

        $metrics = $engine->evaluateStudentAbsence($student, $this->year->id);
        $this->assertEquals(5.0, $metrics['absence_percentage']);
        $this->assertEquals('FIRST_WARNING', $metrics['warning_level']);
        $this->assertFalse($metrics['is_deprived']);

        // Add 3 more days -> 6 days out of 60 = 10.0% -> SECOND_WARNING
        for ($i = 4; $i <= 6; $i++) {
            StudentAttendance::create([
                'student_id'       => $student->id,
                'branch_id'        => $this->branch1->id,
                'academic_year_id' => $this->year->id,
                'record_date'      => Carbon::parse('2026-09-01')->addDays($i)->format('Y-m-d'),
                'status'           => 'ABSENT',
                'recorded_by'      => $this->branchUser1->id,
            ]);
        }

        $metrics = $engine->evaluateStudentAbsence($student, $this->year->id);
        $this->assertEquals(10.0, $metrics['absence_percentage']);
        $this->assertEquals('SECOND_WARNING', $metrics['warning_level']);

        // Add 3 more days -> 9 days out of 60 = 15.0% -> FINAL_WARNING
        for ($i = 7; $i <= 9; $i++) {
            StudentAttendance::create([
                'student_id'       => $student->id,
                'branch_id'        => $this->branch1->id,
                'academic_year_id' => $this->year->id,
                'record_date'      => Carbon::parse('2026-09-01')->addDays($i)->format('Y-m-d'),
                'status'           => 'ABSENT',
                'recorded_by'      => $this->branchUser1->id,
            ]);
        }

        $metrics = $engine->evaluateStudentAbsence($student, $this->year->id);
        $this->assertEquals(15.0, $metrics['absence_percentage']);
        $this->assertEquals('FINAL_WARNING', $metrics['warning_level']);
    }

    public function test_issue_notice_locks_absent_records_retroactively(): void
    {
        $student = $this->createStudent();
        $engine = app(AbsenceCalculationEngineService::class);

        for ($i = 1; $i <= 12; $i++) {
            StudentAttendance::create([
                'student_id'       => $student->id,
                'branch_id'        => $this->branch1->id,
                'academic_year_id' => $this->year->id,
                'record_date'      => Carbon::parse('2026-09-01')->addDays($i)->format('Y-m-d'),
                'status'           => 'ABSENT',
                'recorded_by'      => $this->branchUser1->id,
            ]);
        }

        $notice = $engine->issueNoticeIfEligible($student, $this->hqAdmin);

        $this->assertNotNull($notice);
        $this->assertEquals('EXPULSION_NOTICE', $notice->warning_level);
        $this->assertDatabaseHas('attendance_warning_notices', [
            'id'            => $notice->id,
            'student_id'    => $student->id,
            'warning_level' => 'EXPULSION_NOTICE',
        ]);

        // Verify locked attendance records
        $lockedCount = StudentAttendance::where('student_id', $student->id)
            ->whereNotNull('modification_reason')
            ->count();
        $this->assertEquals(12, $lockedCount);
    }

    // =========================================================================
    // 2. Student State Machine Tests (Option B)
    // =========================================================================

    public function test_illegal_status_jump_is_blocked(): void
    {
        $student = $this->createStudent(['academic_status' => 'NEW_DRAFT']);
        $stateMachine = app(StudentStateMachineService::class);

        $this->expectException(\DomainException::class);
        $stateMachine->transition($student, 'GRADUATED', 'محاولة تخطي المراحل غير قانونية', null, $this->hqAdmin);
    }

    public function test_branch_user_cannot_directly_mutate_status_to_suspended(): void
    {
        $student = $this->createStudent(['academic_status' => 'ENROLLED_ACTIVE']);

        $response = $this->actingAs($this->branchUser1)->postJson("/api/v1/students/{$student->id}/file/change-status", [
            'new_status' => 'SUSPENDED',
            'reason'     => 'طلب إيقاف قيد مباشر من الفرع بدون رفع طلب للإدارة',
        ]);

        $response->assertStatus(403);
        $this->assertStringContainsString('غير مصرح', $response->json('message'));
        $this->assertEquals('ENROLLED_ACTIVE', $student->fresh()->academic_status);
    }

    public function test_branch_user_submits_status_request_with_attachment_option_b(): void
    {
        $student = $this->createStudent(['academic_status' => 'ENROLLED_ACTIVE']);
        $dummyPdf = UploadedFile::fake()->createWithContent('official_suspension_memo.pdf', "%PDF-1.4\nTest PDF content with magic bytes\n%%EOF");

        $response = $this->actingAs($this->branchUser1)->postJson("/api/v1/students/{$student->id}/file/status-requests", [
            'request_type'            => 'PAUSE',
            'target_academic_year_id' => $this->year->id,
            'reason'                  => 'طلب إيقاف قيد رسمي نظراً لظروف صحية قاهرة معتمدة بتقرير طبي',
            'document'                => $dummyPdf,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('enrollment_status_requests', [
            'student_id'   => $student->id,
            'request_type' => 'PAUSE',
            'branch_status'=> 'APPROVED',
            'hq_status'    => 'PENDING',
            'final_status' => 'PENDING',
        ]);
    }

    public function test_central_hq_approves_status_request_and_applies_suspended_status(): void
    {
        $student = $this->createStudent(['academic_status' => 'ENROLLED_ACTIVE']);

        $req = EnrollmentStatusRequest::create([
            'student_id'              => $student->id,
            'request_type'            => 'PAUSE',
            'target_academic_year_id' => $this->year->id,
            'reason'                  => 'طلب إيقاف قيد رسمي لأسباب معتمدة',
            'document_path'           => 'secure_vault/test_memo.pdf',
            'branch_status'           => 'APPROVED',
            'hq_status'               => 'PENDING',
            'final_status'            => 'PENDING',
            'created_by'              => $this->branchUser1->id,
            'branch_reviewed_by'      => $this->branchUser1->id,
        ]);

        // Branch user cannot approve HQ status
        $forbiddenResponse = $this->actingAs($this->branchUser1)->postJson("/api/v1/student-workflow/status-request/{$req->id}/action", [
            'action'         => 'APPROVE',
            'decision_notes' => 'محاولة اعتماد من الفرع',
        ]);
        $forbiddenResponse->assertStatus(403);

        // Submitter cannot approve their own request even if they gained HQ access (Four-Eyes Principle)
        $selfApproveResponse = $this->actingAs($this->branchUser1)->postJson("/api/v1/student-workflow/status-request/{$req->id}/action", [
            'action' => 'APPROVE',
        ]);
        $selfApproveResponse->assertStatus(403);

        // HQ Approves
        $hqResponse = $this->actingAs($this->hqAdmin)->postJson("/api/v1/student-workflow/status-request/{$req->id}/action", [
            'action'         => 'APPROVE',
            'decision_notes' => 'تمت الموافقة المركزية على إيقاف القيد بناءً على المرفقات المعتمدة',
        ]);

        $hqResponse->assertOk();
        $this->assertEquals('SUSPENDED', $student->fresh()->academic_status);
        $this->assertEquals('APPROVED', $req->fresh()->final_status);
    }

    // =========================================================================
    // 3. Branch Transfer Handshake Validation Tests
    // =========================================================================

    public function test_branch_transfer_requires_central_memo_before_receiving_branch_decision(): void
    {
        $student = $this->createStudent(['branch_id' => $this->branch1->id]);

        $transfer = StudentTransfer::create([
            'student_id'       => $student->id,
            'from_branch_id'   => $this->branch1->id,
            'to_branch_id'     => $this->branch2->id,
            'academic_year_id' => $this->year->id,
            'reason'           => 'انتقال سكن ولي الأمر إلى مدينة بنغازي',
            'requested_by'     => $this->branchUser1->id,
            'status'           => 'PENDING',
        ]);

        // Receiving branch tries to approve BEFORE central memo -> Blocked with 422
        $prematureResponse = $this->actingAs($this->branchUser2)->postJson("/api/v1/student-workflow/transfer-request/{$transfer->id}/step", [
            'step'   => 'RECEIVING_BRANCH',
            'status' => 'APPROVED',
            'notes'  => 'موافقة الفرع المستقبل قبل الإحالة المركزية',
        ]);

        $prematureResponse->assertStatus(422);
        $this->assertStringContainsString('تسلسل الإجراءات غير مكتمل', $prematureResponse->json('message'));
        $this->assertEquals($this->branch1->id, $student->fresh()->branch_id);

        // Step 2: HQ Issues Central Memo
        $hqResponse = $this->actingAs($this->hqAdmin)->postJson("/api/v1/student-workflow/transfer-request/{$transfer->id}/step", [
            'step'  => 'CENTRAL_MEMO',
            'notes' => 'إحالة رسمية رقم 442/2026 معتمدة من شؤون الطلاب المركزية',
        ]);
        $hqResponse->assertOk();
        $this->assertNotNull($transfer->fresh()->central_affairs_approved_at);

        // Step 3: Receiving branch can now approve
        $acceptResponse = $this->actingAs($this->branchUser2)->postJson("/api/v1/student-workflow/transfer-request/{$transfer->id}/step", [
            'step'   => 'RECEIVING_BRANCH',
            'status' => 'APPROVED',
            'notes'  => 'تم ضم الطالب رسمياً وتسكينه في شعبة بنغازي',
        ]);

        $acceptResponse->assertOk();
        $this->assertEquals('APPROVED', $transfer->fresh()->status);
        $this->assertEquals($this->branch2->id, $student->fresh()->branch_id);
    }
}
