<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\GradeBatch;
use App\Models\StudentGrade;
use App\Models\StudentTransfer;
use App\Models\EnrollmentStatusRequest;
use App\Models\Department;
use App\Models\StudyYear;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\SystemAuditTrail;
use App\Services\TotpService;
use App\Services\LoginSecurityService;
use App\Services\SecureFileVaultService;
use App\Services\DocumentLedgerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class SecurityPhaseOneComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $branch1User;
    protected User $branch2User;
    protected Branch $branch1;
    protected Branch $branch2;
    protected Department $department;
    protected StudyYear $studyYear;
    protected AcademicYear $academicYear;
    protected Course $course;
    protected Student $studentBranch1;
    protected Student $studentBranch2;

    protected function setUp(): void
    {
        parent::setUp();

        $roleSuper = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'مدير عام', 'scope_type' => 'GLOBAL_SCOPE']);
        $roleBranch = Role::firstOrCreate(['name' => 'branch_officer'], ['display_name' => 'موظف فرع', 'scope_type' => 'BRANCH_SCOPE']);

        $this->branch1 = Branch::create(['name' => 'فرع طرابلس', 'code' => 'TRP', 'city' => 'طرابلس', 'is_active' => true]);
        $this->branch2 = Branch::create(['name' => 'فرع بنغازي', 'code' => 'BNG', 'city' => 'بنغازي', 'is_active' => true]);

        $this->department = Department::create(['name' => 'الشريعة', 'code' => 'SHR']);
        $this->studyYear = StudyYear::create(['name' => 'السنة الأولى', 'level_order' => 1]);
        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'code' => '2026',
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_current' => true,
        ]);
        $this->course = Course::create([
            'academic_year_id' => $this->academicYear->id,
            'study_year_id' => $this->studyYear->id,
            'department_id' => $this->department->id,
            'semester' => 1,
            'code' => 'FIQH101',
            'name' => 'الفقه الإسلامي 1',
            'credit_hours' => 3,
            'weekly_hours' => 3,
            'max_score' => 100,
            'pass_grade' => 50,
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'المدير العام المركزي',
            'email' => 'hq_admin@iiis.sch.ly',
            'password' => Hash::make('StrongPassword2026!'),
            'role_id' => $roleSuper->id,
            'branch_id' => null,
            'must_change_password' => false,
        ]);

        $this->branch1User = User::create([
            'name' => 'موظف طرابلس',
            'email' => 'officer_trp@iiis.sch.ly',
            'password' => Hash::make('StrongPassword2026!'),
            'role_id' => $roleBranch->id,
            'branch_id' => $this->branch1->id,
            'must_change_password' => false,
        ]);

        $this->branch2User = User::create([
            'name' => 'موظف بنغازي',
            'email' => 'officer_bng@iiis.sch.ly',
            'password' => Hash::make('StrongPassword2026!'),
            'role_id' => $roleBranch->id,
            'branch_id' => $this->branch2->id,
            'must_change_password' => false,
        ]);

        $this->studentBranch1 = Student::create([
            'academic_number' => 'TRP-2026-001',
            'national_id' => '119900000001',
            'branch_id' => $this->branch1->id,
            'department_id' => $this->department->id,
            'current_study_year_id' => $this->studyYear->id,
            'enrolled_academic_year_id' => $this->academicYear->id,
            'first_name' => 'أحمد',
            'father_name' => 'محمد',
            'grandfather_name' => 'سالم',
            'family_name' => 'الطرابلسي',
            'mother_name' => 'فاطمة',
            'gender' => 'MALE',
            'birth_date' => '2005-01-01',
            'birth_place' => 'طرابلس',
            'nationality' => 'ليبي',
            'phone' => '0910000001',
            'guardian_phone' => '0920000001',
            'academic_status' => 'ENROLLED_ACTIVE',
        ]);

        $this->studentBranch2 = Student::create([
            'academic_number' => 'BNG-2026-001',
            'national_id' => '119900000002',
            'branch_id' => $this->branch2->id,
            'department_id' => $this->department->id,
            'current_study_year_id' => $this->studyYear->id,
            'enrolled_academic_year_id' => $this->academicYear->id,
            'first_name' => 'خالد',
            'father_name' => 'عمر',
            'grandfather_name' => 'علي',
            'family_name' => 'البرغثي',
            'mother_name' => 'خديجة',
            'gender' => 'MALE',
            'birth_date' => '2005-02-02',
            'birth_place' => 'بنغازي',
            'nationality' => 'ليبي',
            'phone' => '0910000002',
            'guardian_phone' => '0920000002',
            'academic_status' => 'ENROLLED_ACTIVE',
        ]);
    }

    // =========================================================================
    // 1.1: Passwords Policy & TOTP MFA
    // =========================================================================

    public function test_forced_password_change_middleware_redirects_user_until_password_is_changed(): void
    {
        $this->branch1User->update(['must_change_password' => true]);

        // Attempt to access dashboard
        $response = $this->actingAs($this->branch1User)->get('/');
        $response->assertRedirect(route('password.change'));

        // Change password using compliant strong credentials
        $changeResponse = $this->actingAs($this->branch1User)->post('/password/update', [
            'current_password' => 'StrongPassword2026!',
            'password' => 'NewSecurePass@2026#X',
            'password_confirmation' => 'NewSecurePass@2026#X',
        ]);

        $changeResponse->assertRedirect('/');
        $this->assertFalse((bool) $this->branch1User->fresh()->must_change_password);
        $this->assertTrue(Hash::check('NewSecurePass@2026#X', $this->branch1User->fresh()->password));
    }

    public function test_totp_mfa_flow_with_valid_token_and_recovery_codes(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $recoveryCodes = $totp->generateRecoveryCodes(4);

        $this->superAdmin->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => Carbon::now(),
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
        ]);

        // 1. Attempt login with credentials
        $loginResponse = $this->post('/login', [
            'email' => 'hq_admin@iiis.sch.ly',
            'password' => 'StrongPassword2026!',
        ]);

        // Must redirect to MFA challenge
        $loginResponse->assertRedirect(route('mfa.challenge'));
        $this->assertFalse(session('mfa_verified'));

        // 2. Submit wrong TOTP code
        $wrongMfa = $this->post('/mfa/verify', ['code' => '000000']);
        $wrongMfa->assertSessionHasErrors('code');

        // 3. Submit valid TOTP code
        $validCode = $totp->getCode($secret);
        $validMfa = $this->post('/mfa/verify', ['code' => $validCode]);
        $validMfa->assertRedirect('/');
        $this->assertTrue(session('mfa_verified'));
        $this->assertAuthenticatedAs($this->superAdmin);
    }

    // =========================================================================
    // 1.2: Progressive Login Lockout & Security Headers
    // =========================================================================

    public function test_login_security_service_enforces_tiered_progressive_lockout(): void
    {
        $security = app(LoginSecurityService::class);
        $email = 'target_user@iiis.sch.ly';
        $ip = '192.168.10.55';

        // 5 failures -> Tier 1 (1 min)
        for ($i = 1; $i <= 5; $i++) {
            $security->recordFailedAttempt($email, $ip);
        }

        $lockout = $security->checkLockout($email, $ip);
        $this->assertTrue($lockout['is_locked']);
        $this->assertGreaterThan(0, $lockout['seconds']);

        // Clear attempts on success
        $security->clearAttempts($email, $ip);
        $lockoutAfterClear = $security->checkLockout($email, $ip);
        $this->assertFalse($lockoutAfterClear['is_locked']);
    }

    public function test_security_headers_are_present_on_all_responses(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    // =========================================================================
    // 1.4: IDOR Prevention & Multi-tenant Branch Isolation
    // =========================================================================

    public function test_branch_user_cannot_view_or_mutate_other_branch_students(): void
    {
        // Branch 1 user tries to view Branch 2 student via StudentFileController (blocked by BranchScope/Policy)
        $response = $this->actingAs($this->branch1User)->getJson("/api/v1/students/{$this->studentBranch2->id}");
        $this->assertTrue(in_array($response->status(), [403, 404]));

        // Branch 1 user tries to update Branch 2 student personal info
        $updateResponse = $this->actingAs($this->branch1User)->putJson("/api/v1/students/{$this->studentBranch2->id}", [
            'first_name' => 'تعديل غير مشروع',
            'phone' => '0919999999',
        ]);
        $this->assertTrue(in_array($updateResponse->status(), [403, 404]));

        // Super Admin (HQ) has full cross-branch visibility
        $hqResponse = $this->actingAs($this->superAdmin)->getJson("/api/v1/students/{$this->studentBranch2->id}");
        $hqResponse->assertStatus(200);
    }

    // =========================================================================
    // 1.5: Public Document Verification & HMAC-SHA256
    // =========================================================================

    public function test_public_verification_hmac_sha256_and_privacy_masking(): void
    {
        $ledger = app(DocumentLedgerService::class);
        $doc = $ledger->issueDocument(
            documentType: 'ENROLLMENT_CERTIFICATE',
            student: $this->studentBranch1,
            signatoryPosition: 'مدير عام شؤون الطلاب'
        );

        $this->assertNotEmpty($doc->document_uuid);
        $this->assertNotEmpty($doc->hash_signature);

        // 1. Web verification URL
        $webRes = $this->get('/verify/' . $doc->document_uuid);
        $webRes->assertOk()
            ->assertSee('وثيقة أصلية ومعتمدة رسمياً')
            ->assertSee('HMAC-SHA256')
            ->assertDontSee($this->studentBranch1->national_id)
            ->assertDontSee($this->studentBranch1->phone);

        // 2. Replaced document state
        $ledger->replaceDocument($doc->document_uuid, 'تم إصدار وثيقة بديلة محدثة');
        $replacedRes = $this->get('/verify/' . $doc->document_uuid);
        $replacedRes->assertOk()
            ->assertSee('وثيقة مستبدلة رسمياً بإصدار أحدث');
    }

    // =========================================================================
    // 1.6: Secure File Vault & Magic Bytes Inspection
    // =========================================================================

    public function test_secure_file_vault_rejects_malicious_spoofed_files_and_encrypts_storage(): void
    {
        $vault = app(SecureFileVaultService::class);

        // A. Spoofed PHP shell pretending to be a PDF
        $maliciousContent = "<?php echo 'HACKED'; system(\$_GET['cmd']); ?>";
        $spoofedFile = UploadedFile::fake()->createWithContent('document.pdf', $maliciousContent);

        $result = $vault->storeSecure($spoofedFile, 'documents');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('غير مسموح', $result['error']);

        // B. Legitimate PDF
        $pdfContent = "%PDF-1.4\n1 0 obj\n<<\n>>\nendobj\ntrailer\n<<\n>>\n%%EOF";
        $validPdf = UploadedFile::fake()->createWithContent('valid_certificate.pdf', $pdfContent);

        $vaultResult = $vault->storeSecure($validPdf, 'student_docs');
        $this->assertTrue($vaultResult['success']);
        $this->assertNotEmpty($vaultResult['path']);

        // Verify storage is encrypted at rest
        $storedRawBytes = Storage::disk('local')->get($vaultResult['path']);
        $this->assertStringNotContainsString('%PDF-1.4', $storedRawBytes); // Must be ciphertext

        // Decrypt stream check
        $decryptedContent = $vault->retrieveDecrypted($vaultResult['path']);
        $this->assertStringStartsWith('%PDF-1.4', $decryptedContent);
    }

    // =========================================================================
    // 1.7: Tamper-Evident SHA-256 Audit Trail Hash Chain
    // =========================================================================

    public function test_audit_trail_cryptographic_hash_chain_and_append_only_immutability(): void
    {
        // 1. Clear any transient audit records for pristine test
        SystemAuditTrail::withoutGlobalScopes()->delete();

        // 2. Create consecutive audit records
        $entry1 = SystemAuditTrail::log('USER_LOGIN', 'تسجيل دخول رقم 1', ['ip' => '127.0.0.1'], $this->superAdmin->id);
        $entry2 = SystemAuditTrail::log('STUDENT_UPDATED', 'تحديث قيد طالب', ['id' => 10], $this->superAdmin->id);
        $entry3 = SystemAuditTrail::log('GRADE_LOCKED', 'إقفال كشف درجات', ['batch' => 5], $this->superAdmin->id);

        $this->assertNotNull($entry1);
        $this->assertNotNull($entry2);
        $this->assertNotNull($entry3);

        // Verify chain link
        $this->assertEquals(str_repeat('0', 64), $entry1->previous_hash);
        $this->assertEquals($entry1->record_hash, $entry2->previous_hash);
        $this->assertEquals($entry2->record_hash, $entry3->previous_hash);

        // Verify chain integrity method
        $check = SystemAuditTrail::verifyChainIntegrity();
        $this->assertTrue($check['intact']);
        $this->assertNull($check['broken_id']);

        // 3. Immutability enforcement: attempting to update throws RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SECURITY VIOLATION');
        $entry2->update(['description' => 'تعديل أمني غير مصرح به']);
    }

    // =========================================================================
    // 1.8: Four-Eyes Principle (الرقابة الثنائية)
    // =========================================================================

    public function test_four_eyes_principle_blocks_grade_batch_submitter_from_approving(): void
    {
        // Create grade batch submitted by SuperAdmin
        $batch = GradeBatch::create([
            'branch_id' => $this->branch1->id,
            'department_id' => $this->department->id,
            'academic_year_id' => $this->academicYear->id,
            'study_year_id' => $this->studyYear->id,
            'semester' => 1,
            'batch_code' => 'BATCH-TEST-4EYES',
            'status' => 'SUBMITTED_TO_HQ',
            'submitted_by' => $this->superAdmin->id,
            'submitted_at' => Carbon::now(),
        ]);

        StudentGrade::create([
            'grade_batch_id' => $batch->id,
            'student_id' => $this->studentBranch1->id,
            'course_id' => $this->course->id,
            'coursework_grade' => 35,
            'final_grade' => 45,
            'total_grade' => 80,
            'status' => 'PASS',
        ]);

        // SuperAdmin attempts to approve the batch they submitted
        $response = $this->actingAs($this->superAdmin)->postJson("/api/v1/grades/batches/{$batch->id}/approve-hq");
        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Four-Eyes Principle', $response->json('message'));

        // Another independent HQ user can approve
        $independentHq = User::create([
            'name' => 'مدير امتحانات آخر',
            'email' => 'exams_director_2@iiis.sch.ly',
            'password' => Hash::make('StrongPassword2026!'),
            'role_id' => $this->superAdmin->role_id,
            'branch_id' => null,
            'must_change_password' => false,
        ]);

        $successResponse = $this->actingAs($independentHq)->postJson("/api/v1/grades/batches/{$batch->id}/approve-hq");
        $successResponse->assertStatus(200);
        $this->assertEquals('HQ_APPROVED', $batch->fresh()->status);
    }

    public function test_four_eyes_principle_blocks_transfer_requester_from_approving_transfer(): void
    {
        $transfer = StudentTransfer::create([
            'student_id' => $this->studentBranch1->id,
            'from_branch_id' => $this->branch1->id,
            'to_branch_id' => $this->branch2->id,
            'status' => 'PENDING',
            'reason' => 'طلب نقل لدواعي الانتقال السكني',
            'requested_by' => $this->superAdmin->id,
        ]);

        // Requester tries to approve
        $response = $this->actingAs($this->superAdmin)->postJson("/api/v1/student-workflow/transfer-request/{$transfer->id}/step", [
            'step' => 'CENTRAL_MEMO',
            'status' => 'APPROVED',
            'notes' => 'موافقة ذاتية',
        ]);

        $response->assertStatus(403);
        $this->assertStringContainsString('الرقابة الثنائية', $response->json('message'));
    }
}
