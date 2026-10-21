<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\BranchNotice;
use App\Models\Branch;
use App\Models\Nationality;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\SystemAuditTrail;
use App\Models\User;
use App\Services\StudentDocumentService;
use Carbon\Carbon;
use Database\Seeders\AcademicSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * دورة تسجيل الطالب غير الليبي: إدارة الجنسيات، والرفع من الفرع، والاعتماد المركزي، والرفض مع الحذف النهائي.
 */
class NonLibyanRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private User $hq;
    private User $registrar;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);

        $this->hq = User::where('email', 'admin@iiis.sch.ly')->firstOrFail();
        $this->branch = Branch::where('city', 'طرابلس')->firstOrFail();
        $this->registrar = User::factory()->create([
            'role_id' => Role::where('name', 'branch_registrar')->value('id'),
            'branch_id' => $this->branch->id,
            'must_change_password' => false,
        ]);
    }

    private function birthDateForAge(int $age): string
    {
        $reference = Carbon::parse(AcademicYear::where('is_current', true)->firstOrFail()->start_date);

        return $reference->copy()->subYears($age)->format('Y-m-d');
    }

    private function nonLibyanPayload(array $overrides = []): array
    {
        return array_merge([
            'nationality_category' => 'OTHER',
            'passport_number' => 'P' . random_int(1000000, 9999999),
            'nationality' => 'مصري',
            'first_name' => 'يوسف',
            'father_name' => 'حسن',
            'grandfather_name' => 'عمر',
            'family_name' => 'الكيلاني',
            'mother_name' => 'سلمى أحمد',
            'gender' => 'MALE',
            'birth_date' => $this->birthDateForAge(16),
            'birth_place' => 'القاهرة',
            'phone' => '091-4445566',
            'guardian_phone' => '091-5556677',
            'department_id' => \App\Models\Department::first()->id,
            'current_study_year_id' => \App\Models\StudyYear::first()->id,
            'study_type' => 'REGULAR',
            'branch_id' => $this->branch->id,
        ], $overrides);
    }

    /** يُنشئ طالباً غير ليبي من مسار الإدخال ويعيده مع معرّفه. */
    private function createNonLibyanDraft(array $overrides = []): Student
    {
        $response = $this->actingAs($this->hq)->postJson('/api/v1/students', $this->nonLibyanPayload($overrides));
        $response->assertStatus(201);

        return Student::findOrFail($response->json('student.id'));
    }

    /** يرفع المستندات الإلزامية كاملةً كما يفعل الفرع بعد الإدخال. */
    private function attachEveryRequiredDocument(Student $student): void
    {
        foreach (app(StudentDocumentService::class)->requiredTypesFor($student) as $type) {
            StudentDocument::create([
                'student_id' => $student->id,
                'document_type' => $type,
                'original_name' => strtolower($type) . '.pdf',
                'file_size' => 1024,
                'mime_type' => 'application/pdf',
                'is_required' => true,
                'file_path' => 'secure_vault/test/' . $student->id . '/' . $type . '.enc',
                'file_hash' => hash('sha256', $type),
                'uploaded_by' => $this->hq->id,
            ]);
        }
    }

    // ---------------------------------------------------------------
    // 1. إدارة الجنسيات
    // ---------------------------------------------------------------

    public function test_default_nationalities_are_seeded_and_libyan_is_never_in_the_managed_list(): void
    {
        $names = Nationality::activeNames();

        $this->assertContains('مصري', $names);
        $this->assertContains('سوداني', $names);
        $this->assertContains('تونسي', $names);
        $this->assertNotContains('ليبي', $names);
    }

    public function test_hq_adds_a_nationality_and_the_change_is_audited(): void
    {
        $this->actingAs($this->hq)
            ->postJson('/api/v1/admin/settings/nationalities', ['name' => 'إريتري'])
            ->assertStatus(201);

        $this->assertContains('إريتري', Nationality::activeNames());
        $this->assertDatabaseHas('system_audit_trails', ['event_type' => 'NATIONALITY_CREATED']);
    }

    public function test_nationality_name_must_be_unique_and_cannot_be_libyan(): void
    {
        $this->actingAs($this->hq)->postJson('/api/v1/admin/settings/nationalities', ['name' => 'مصري'])
            ->assertStatus(422)->assertJsonValidationErrors(['name']);

        $this->actingAs($this->hq)->postJson('/api/v1/admin/settings/nationalities', ['name' => 'ليبي'])
            ->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_branch_registrar_cannot_manage_the_nationality_list(): void
    {
        $this->actingAs($this->registrar)
            ->postJson('/api/v1/admin/settings/nationalities', ['name' => 'إريتري'])
            ->assertStatus(403);

        $this->assertNotContains('إريتري', Nationality::activeNames());
    }

    public function test_registration_dropdown_lists_the_managed_nationalities(): void
    {
        $this->actingAs($this->hq)->postJson('/api/v1/admin/settings/nationalities', ['name' => 'إريتري'])->assertStatus(201);

        $this->actingAs($this->hq)->get('/')->assertOk()->assertSee('إريتري');
    }

    public function test_deactivated_nationality_is_refused_for_new_students_but_kept_on_an_existing_file(): void
    {
        $student = $this->createNonLibyanDraft(['nationality' => 'مصري']);

        $nationality = Nationality::where('name', 'مصري')->firstOrFail();
        $this->actingAs($this->hq)
            ->putJson("/api/v1/admin/settings/nationalities/{$nationality->id}", ['is_active' => false])
            ->assertOk();

        $this->actingAs($this->hq)->postJson('/api/v1/students', $this->nonLibyanPayload(['nationality' => 'مصري']))
            ->assertStatus(422)->assertJsonValidationErrors(['nationality']);

        // The existing file keeps its nationality until someone changes it.
        $this->actingAs($this->hq)->putJson("/api/v1/students/{$student->id}", $this->nonLibyanPayload(['nationality' => 'مصري']))
            ->assertOk();
    }

    // ---------------------------------------------------------------
    // 2. التقديم والاعتماد
    // ---------------------------------------------------------------

    public function test_non_libyan_registration_is_saved_as_a_draft_without_an_academic_number(): void
    {
        $student = $this->createNonLibyanDraft();

        $this->assertSame('NEW_DRAFT', $student->academic_status);
        $this->assertNull($student->academic_number);
        $this->assertNull($student->approved_at);
    }

    public function test_submission_is_refused_until_every_required_document_is_attached(): void
    {
        $student = $this->createNonLibyanDraft();

        $response = $this->actingAs($this->hq)->postJson("/api/v1/students/{$student->id}/submit-hq");

        $response->assertStatus(422);
        $this->assertContains('BASIC_EDUCATION_CERT', $response->json('missing_documents'));
        $this->assertContains('FOREIGN_BIRTH_CERT', $response->json('missing_documents'));
        $this->assertSame('NEW_DRAFT', $student->fresh()->academic_status);
    }

    public function test_submitted_file_waits_for_hq_and_still_has_no_academic_number(): void
    {
        $student = $this->createNonLibyanDraft();
        $this->attachEveryRequiredDocument($student);

        $this->actingAs($this->registrar)->postJson("/api/v1/students/{$student->id}/submit-hq")->assertOk();

        $fresh = $student->fresh();
        $this->assertSame('PENDING_HQ', $fresh->academic_status);
        $this->assertNull($fresh->academic_number);
    }

    public function test_hq_approval_issues_the_academic_number_and_activates_the_student(): void
    {
        $student = $this->createNonLibyanDraft();
        $this->attachEveryRequiredDocument($student);
        $this->actingAs($this->registrar)->postJson("/api/v1/students/{$student->id}/submit-hq")->assertOk();

        $this->actingAs($this->hq)->postJson("/api/v1/students/{$student->id}/approve-hq")->assertOk();

        $fresh = $student->fresh();
        $this->assertNotNull($fresh->academic_number);
        $this->assertSame('ENROLLED_ACTIVE', $fresh->academic_status);
        $this->assertDatabaseHas('system_audit_trails', ['event_type' => 'NON_LIBYAN_ENROLLMENT_APPROVED']);
    }

    public function test_approval_is_refused_before_the_branch_submits_the_file(): void
    {
        $student = $this->createNonLibyanDraft();
        $this->attachEveryRequiredDocument($student);

        $this->actingAs($this->hq)->postJson("/api/v1/students/{$student->id}/approve-hq")->assertStatus(422);

        $this->assertNull($student->fresh()->academic_number);
    }

    public function test_branch_registrar_cannot_approve_a_non_libyan_file(): void
    {
        $student = $this->createNonLibyanDraft();
        $this->attachEveryRequiredDocument($student);
        $this->actingAs($this->registrar)->postJson("/api/v1/students/{$student->id}/submit-hq")->assertOk();

        $this->actingAs($this->registrar)->postJson("/api/v1/students/{$student->id}/approve-hq")->assertStatus(403);

        $this->assertNull($student->fresh()->academic_number);
    }

    public function test_libyan_registration_still_receives_its_academic_number_immediately(): void
    {
        $response = $this->actingAs($this->hq)->postJson('/api/v1/students', [
            'nationality_category' => 'LIBYAN',
            'national_id' => '1' . random_int(10000000000, 99999999999),
            'first_name' => 'محمد', 'father_name' => 'علي', 'grandfather_name' => 'أحمد', 'family_name' => 'الورفلي',
            'mother_name' => 'فاطمة محمود', 'birth_date' => $this->birthDateForAge(16), 'birth_place' => 'طرابلس',
            'phone' => '091-1112233', 'guardian_phone' => '091-2223344',
            'department_id' => \App\Models\Department::first()->id,
            'current_study_year_id' => \App\Models\StudyYear::first()->id,
            'study_type' => 'REGULAR', 'branch_id' => $this->branch->id,
        ]);

        $response->assertStatus(201);
        $this->assertNotEmpty($response->json('student.academic_number'));
        $this->assertSame('ENROLLED_ACTIVE', $response->json('student.academic_status'));
    }

    // ---------------------------------------------------------------
    // 3. الرفض والحذف التلقائي
    // ---------------------------------------------------------------

    public function test_hq_rejection_purges_the_student_its_documents_and_their_files(): void
    {
        Storage::fake('local');
        $student = $this->createNonLibyanDraft();
        $this->attachEveryRequiredDocument($student);
        $this->actingAs($this->registrar)->postJson("/api/v1/students/{$student->id}/submit-hq")->assertOk();

        $storedPath = StudentDocument::where('student_id', $student->id)->value('file_path');
        Storage::disk('local')->put($storedPath, 'encrypted-bytes');
        $passport = $student->passport_number;
        $studentId = $student->id;

        $this->actingAs($this->hq)
            ->postJson("/api/v1/students/{$studentId}/reject-hq", ['rejection_reason' => 'الشهادة المعادلة غير مصدّقة'])
            ->assertOk();

        $this->assertDatabaseMissing('students', ['id' => $studentId]);
        $this->assertDatabaseMissing('student_documents', ['student_id' => $studentId]);
        Storage::disk('local')->assertMissing($storedPath);
        $this->assertSame(0, Student::where('nationality_category', 'OTHER')->whereNotNull('academic_number')->count());
    }

    public function test_rejection_is_audited_with_its_reason_but_without_the_students_personal_data(): void
    {
        $student = $this->createNonLibyanDraft();
        $this->attachEveryRequiredDocument($student);
        $this->actingAs($this->registrar)->postJson("/api/v1/students/{$student->id}/submit-hq")->assertOk();
        $passport = $student->passport_number;

        $this->actingAs($this->hq)
            ->postJson("/api/v1/students/{$student->id}/reject-hq", ['rejection_reason' => 'جواز منتهي الصلاحية'])
            ->assertOk();

        $audit = SystemAuditTrail::where('event_type', 'NON_LIBYAN_ENROLLMENT_REJECTED')->firstOrFail();
        $this->assertStringContainsString('جواز منتهي الصلاحية', $audit->description);
        $this->assertStringNotContainsString($passport, $audit->description . json_encode($audit->payload));
        $this->assertStringNotContainsString('الكيلاني', $audit->description . json_encode($audit->payload));
    }

    public function test_rejection_requires_a_reason_and_keeps_the_file(): void
    {
        $student = $this->createNonLibyanDraft();
        $this->attachEveryRequiredDocument($student);
        $this->actingAs($this->registrar)->postJson("/api/v1/students/{$student->id}/submit-hq")->assertOk();

        $this->actingAs($this->hq)->postJson("/api/v1/students/{$student->id}/reject-hq", [])->assertStatus(422);

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_a_file_that_is_not_awaiting_hq_cannot_be_rejected_into_a_purge(): void
    {
        $student = $this->createNonLibyanDraft();

        $this->actingAs($this->hq)
            ->postJson("/api/v1/students/{$student->id}/reject-hq", ['rejection_reason' => 'سبب كافٍ للاختبار'])
            ->assertStatus(422);

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    // ---------------------------------------------------------------
    // 4. قائمة المراجعة المركزية
    // ---------------------------------------------------------------

    public function test_pending_list_shows_only_submitted_non_libyan_files_with_their_documents(): void
    {
        $draft = $this->createNonLibyanDraft();
        $submitted = $this->createNonLibyanDraft(['passport_number' => 'Z7654321']);
        $this->attachEveryRequiredDocument($submitted);
        $this->actingAs($this->registrar)->postJson("/api/v1/students/{$submitted->id}/submit-hq")->assertOk();

        $response = $this->actingAs($this->hq)->getJson('/api/v1/students/non-libyan/pending');

        $response->assertOk();
        $ids = collect($response->json('students'))->pluck('id')->all();
        $this->assertContains($submitted->id, $ids);
        $this->assertNotContains($draft->id, $ids);
        $this->assertNotEmpty($response->json('students.0.documents'));
        $this->assertArrayHasKey('file_url', $response->json('students.0.documents.0'));
    }

    public function test_branch_registrar_cannot_open_the_central_review_list(): void
    {
        $this->actingAs($this->registrar)->getJson('/api/v1/students/non-libyan/pending')->assertStatus(403);
    }

    // ---------------------------------------------------------------
    // 5. الاستيراد الجماعي والإشعارات
    // ---------------------------------------------------------------

    public function test_batch_import_saves_a_non_libyan_row_as_a_draft_and_issues_no_number(): void
    {
        $passport = 'IMP' . random_int(100000, 999999);

        $response = $this->actingAs($this->hq)->postJson('/api/v1/students/import-batch', [
            'default_branch_id' => $this->branch->id,
            'default_department_id' => \App\Models\Department::first()->id,
            'default_study_year_id' => \App\Models\StudyYear::first()->id,
            'students' => [[
                'national_id' => '',
                'passport_number' => $passport,
                'nationality' => 'مصري',
                'first_name' => 'كريم', 'father_name' => 'سعيد', 'grandfather_name' => 'فؤاد', 'family_name' => 'الحداد',
                'mother_name' => 'هدى', 'gender' => 'ذكر', 'birth_date' => '2010-06-15', 'birth_place' => 'القاهرة',
                'phone' => '091-7778899', 'guardian_phone' => '091-8889900', 'study_type' => 'نظامي',
            ]],
        ]);

        $response->assertOk();
        $student = Student::where('passport_number', $passport)->firstOrFail();
        $this->assertSame('NEW_DRAFT', $student->academic_status);
        $this->assertSame('OTHER', $student->nationality_category);
        $this->assertNull($student->academic_number);
    }

    public function test_batch_import_refuses_a_non_libyan_row_whose_nationality_is_not_managed(): void
    {
        $response = $this->actingAs($this->hq)->postJson('/api/v1/students/import-batch', [
            'default_branch_id' => $this->branch->id,
            'default_department_id' => \App\Models\Department::first()->id,
            'default_study_year_id' => \App\Models\StudyYear::first()->id,
            'students' => [[
                'national_id' => '',
                'passport_number' => 'IMX' . random_int(100000, 999999),
                'nationality' => 'فرنسي',
                'first_name' => 'كريم', 'father_name' => 'سعيد', 'grandfather_name' => 'فؤاد', 'family_name' => 'الحداد',
                'mother_name' => 'هدى', 'gender' => 'ذكر', 'birth_date' => '2010-06-15', 'birth_place' => 'باريس',
                'phone' => '091-7778899', 'guardian_phone' => '091-8889900', 'study_type' => 'نظامي',
            ]],
        ]);

        $this->assertSame(0, Student::where('nationality', 'فرنسي')->count());
        $this->assertSame(0, $response->json('imported_count'));
    }

    public function test_hq_approval_leaves_a_notice_for_the_branch_with_the_issued_number(): void
    {
        $student = $this->createNonLibyanDraft();
        $this->attachEveryRequiredDocument($student);
        $this->actingAs($this->registrar)->postJson("/api/v1/students/{$student->id}/submit-hq")->assertOk();

        $this->actingAs($this->hq)->postJson("/api/v1/students/{$student->id}/approve-hq")->assertOk();

        $notice = BranchNotice::where('title', 'اعتماد طالب غير ليبي')->firstOrFail();
        $this->assertSame($this->branch->id, $notice->branch_id);
        $this->assertStringContainsString($student->fresh()->academic_number, $notice->body);
    }

    public function test_hq_rejection_leaves_a_notice_with_the_reason_and_without_the_students_data(): void
    {
        $student = $this->createNonLibyanDraft();
        $this->attachEveryRequiredDocument($student);
        $this->actingAs($this->registrar)->postJson("/api/v1/students/{$student->id}/submit-hq")->assertOk();
        $passport = $student->passport_number;

        $this->actingAs($this->hq)
            ->postJson("/api/v1/students/{$student->id}/reject-hq", ['rejection_reason' => 'صورة الجواز غير واضحة'])
            ->assertOk();

        $notice = BranchNotice::where('title', 'رفض طلب تسجيل طالب غير ليبي')->firstOrFail();
        $this->assertStringContainsString('صورة الجواز غير واضحة', $notice->body);
        $this->assertStringNotContainsString('الكيلاني', $notice->body);
        $this->assertStringNotContainsString($passport, $notice->body);
    }

    public function test_each_user_sees_only_its_branch_notices_and_can_mark_them_read(): void
    {
        $otherBranch = Branch::create(['name' => 'فرع بنغازي للاختبار', 'code' => 'NT-02', 'city' => 'بنغازي', 'branch_status' => 'ACTIVE']);
        BranchNotice::notify($this->branch->id, 'إشعار طرابلس', 'نص إشعار طرابلس');
        $foreign = BranchNotice::notify($otherBranch->id, 'إشعار بنغازي', 'نص إشعار بنغازي');

        $mine = $this->actingAs($this->registrar)->getJson('/api/v1/notices');
        $mine->assertOk()->assertJsonPath('unread_count', 1);
        $this->assertContains('إشعار طرابلس', collect($mine->json('notices'))->pluck('title')->all());
        $this->assertNotContains('إشعار بنغازي', collect($mine->json('notices'))->pluck('title')->all());

        $this->actingAs($this->registrar)->postJson("/api/v1/notices/{$foreign->id}/read")->assertNotFound();

        $this->actingAs($this->registrar)->postJson('/api/v1/notices/read-all')->assertOk();
        $this->actingAs($this->registrar)->getJson('/api/v1/notices')->assertJsonPath('unread_count', 0);

        $all = $this->actingAs($this->hq)->getJson('/api/v1/notices');
        $this->assertCount(2, $all->json('notices'));
    }

    public function test_every_required_non_libyan_document_is_defined_in_the_catalogue(): void
    {
        $student = $this->createNonLibyanDraft();
        $required = app(StudentDocumentService::class)->requiredTypesFor($student);

        foreach ($required as $type) {
            $this->assertArrayHasKey($type, StudentDocument::DOCUMENT_TYPES);
        }
        $this->assertContains('BASIC_EDUCATION_CERT', $required);
    }
}
