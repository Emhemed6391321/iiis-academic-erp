<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Course;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\StudyYear;
use App\Models\User;
use App\Models\GradeBatch;
use App\Models\StudentGrade;
use App\Models\GradeLog;
use Exception;

class GradeControlAndForensicAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
    }

    public function test_grade_entry_logs_audit_trail_and_enforces_immutability()
    {
        $branch = Branch::where('code', '01')->first();
        $dept = Department::first();
        $studyYear = StudyYear::first();
        $course = Course::first(); // Max coursework: 30, Midterm: 20, Final: 50
        $currentYear = AcademicYear::where('is_current', true)->first();
        $examsOfficer = User::where('email', 'exams.tip@iiis.sch.ly')->first();
        $hqDirector = User::where('email', 'exams.hq@iiis.sch.ly')->first();

        // Create an enrolled student
        $student = Student::withoutGlobalScopes()->create([
            'academic_number' => '01202610001',
            'national_id' => '119900000055',
            'branch_id' => $branch->id,
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'first_name' => 'أسامة',
            'father_name' => 'جمعة',
            'grandfather_name' => 'المهدي',
            'family_name' => 'القذافي',
            'mother_name' => 'خديجة',
            'gender' => 'MALE',
            'birth_date' => '2006-03-10',
            'birth_place' => 'طرابلس',
            'phone' => '091-5551234',
            'guardian_phone' => '091-5555678',
            'study_type' => 'REGULAR',
            'academic_status' => 'ENROLLED_ACTIVE',
            'enrolled_academic_year_id' => $currentYear->id,
        ]);

        // 1. Get Grade Sheet
        $sheetResponse = $this->actingAs($examsOfficer)->getJson(
            "/api/v1/grades/sheet?branch_id={$branch->id}&academic_year_id={$currentYear->id}&study_year_id={$studyYear->id}&department_id={$dept->id}&course_id={$course->id}&semester=1"
        );
        $sheetResponse->assertStatus(200);

        $batchId = $sheetResponse->json('batch.id');
        $studentGradeId = $sheetResponse->json('grades.0.id');

        // 2. Save Grades within Libyan Ministry limits (Coursework: 11, Midterm: 3.5, Final: 25.5 -> Total: 40)
        $saveResponse = $this->actingAs($examsOfficer)->postJson('/api/v1/grades/batch-save', [
            'batch_id' => $batchId,
            'grades' => [
                [
                    'id' => $studentGradeId,
                    'coursework_grade' => 11.00,
                    'midterm_grade' => 3.50,
                    'final_exam_grade' => 25.50,
                ],
            ],
            'reason' => 'رصد درجات الدور الأول للفصل الأول',
        ]);

        $saveResponse->assertStatus(200);

        // Verify calculation (coursework: 11 + final_exam: 25.5 = 36.50 out of 40)
        $gradeRecord = StudentGrade::find($studentGradeId);
        $this->assertEquals(37.00, $gradeRecord->total_grade); // 36.50 rounded half-up on course total
        $this->assertEquals('PASS', $gradeRecord->status);

        // Verify Forensic Grade Logs
        $logs = GradeLog::withoutGlobalScopes()->where('student_grade_id', $studentGradeId)->get();
        $this->assertCount(3, $logs); // coursework, midterm, final

        $finalLog = $logs->where('modified_field', 'final_exam_grade')->first();
        $this->assertNotNull($finalLog);
        $this->assertEquals('25.5', $finalLog->new_value);
        $this->assertEquals($examsOfficer->id, $finalLog->user_id);

        // Verify Immutability: Attempting to update or delete a GradeLog must throw an exception
        try {
            $finalLog->update(['new_value' => '99']);
            $this->fail('Expected exception when attempting to update GradeLog');
        } catch (Exception $e) {
            $this->assertStringContainsString('لا يمكن تعديل سجل التدقيق الجنائي', $e->getMessage());
        }

        try {
            $finalLog->delete();
            $this->fail('Expected exception when attempting to delete GradeLog');
        } catch (Exception $e) {
            $this->assertStringContainsString('لا يمكن حذف سجل التدقيق الجنائي', $e->getMessage());
        }

        // 3. Submit Batch to HQ
        $batch = GradeBatch::find($batchId);
        $submitResponse = $this->actingAs($examsOfficer)->postJson("/api/v1/grades/batches/{$batch->id}/submit-hq");
        $submitResponse->assertStatus(200);
        $this->assertEquals('SUBMITTED_TO_HQ', $batch->fresh()->status);

        // Branch cannot modify grades while submitted
        $modifyAttempt = $this->actingAs($examsOfficer)->postJson('/api/v1/grades/batch-save', [
            'batch_id' => $batchId,
            'grades' => [['id' => $studentGradeId, 'final_exam_grade' => 50]],
        ]);
        $modifyAttempt->assertStatus(500); // Throws exception because batch is locked

        // 4. HQ approves batch & creates SHA-256 seal
        $approveResponse = $this->actingAs($hqDirector)->postJson("/api/v1/grades/batches/{$batch->id}/approve-hq");
        $approveResponse->assertStatus(200);

        $freshBatch = $batch->fresh();
        $this->assertEquals('HQ_APPROVED', $freshBatch->status);
        $this->assertNotNull($freshBatch->batch_digital_hash);
        $this->assertEquals(64, strlen($freshBatch->batch_digital_hash)); // SHA-256 hex string length
    }
}
