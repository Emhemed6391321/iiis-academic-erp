<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\AcademicYear;
use App\Models\StudyYear;
use App\Models\Department;
use App\Models\Course;
use App\Models\Student;
use App\Models\GradeBatch;
use App\Models\StudentGrade;
use App\Services\GradeCalculationEngineService;
use Carbon\Carbon;

class LibyanOfficialGradingLogicTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected AcademicYear $year;
    protected Department $department;
    protected StudyYear $year1;
    protected StudyYear $year2;
    protected StudyYear $year3;
    protected GradeCalculationEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name' => 'super_admin',
            'display_name' => 'المدير العام',
            'scope_type' => 'GLOBAL_SCOPE',
        ]);

        $this->user = User::create([
            'name' => 'المدير العام',
            'email' => 'admin@iiis.sch.ly',
            'password' => bcrypt('Password@2026'),
            'role_id' => $role->id,
            'national_id' => '119800000001',
            'phone' => '091-0000001',
        ]);

        $this->branch = Branch::create([
            'code' => '01',
            'name' => 'فرع طرابلس المركزي',
            'city' => 'طرابلس',
            'address' => 'طريق الشط',
        ]);

        $this->year = AcademicYear::create([
            'code' => '2026-2027',
            'name' => 'العام الأكاديمي 2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_current' => true,
        ]);

        $this->department = Department::create([
            'code' => 'DAWAH_USUL_ALDIN',
            'name' => 'شعبة الدعوة وأصول الدين',
        ]);

        $this->year1 = StudyYear::create(['name' => 'السنة الأولى', 'level_order' => 1]);
        $this->year2 = StudyYear::create(['name' => 'السنة الثانية', 'level_order' => 2]);
        $this->year3 = StudyYear::create(['name' => 'السنة الثالثة', 'level_order' => 3]);

        $this->engine = app(GradeCalculationEngineService::class);
    }

    protected function createStudent(StudyYear $studyYear, string $accNum): Student
    {
        return Student::create([
            'academic_number' => $accNum,
            'national_id' => '1200' . rand(10000000, 99999999),
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'current_study_year_id' => $studyYear->id,
            'first_name' => 'علي',
            'father_name' => 'أحمد',
            'grandfather_name' => 'سالم',
            'family_name' => 'الليبي',
            'mother_name' => 'فاطمة',
            'gender' => 'MALE',
            'birth_date' => '2008-01-01',
            'birth_place' => 'طرابلس',
            'phone' => '091-1111111',
            'guardian_phone' => '092-2222222',
            'study_type' => 'REGULAR',
            'academic_status' => 'ENROLLED_ACTIVE',
            'enrolled_academic_year_id' => $this->year->id,
        ]);
    }

    /**
     * Test 1: Year 1 (80-weight course) with normal full pass.
     */
    public function test_year1_80_weight_course_normal_pass(): void
    {
        $course = Course::create([
            'study_year_id' => $this->year1->id,
            'department_id' => $this->department->id,
            'semester' => 1,
            'code' => 'FQH101',
            'name' => 'الفقه',
            'credit_hours' => 2,
            'weekly_hours' => 2,
            'assessment_system' => 'SEMESTER_SYSTEM',
            'max_coursework_grade' => 12.00,
            'max_midterm_grade' => 4.00,
            'max_final_grade' => 28.00,
            'pass_grade' => 40.00,
            'max_score' => 80.00,
            'pass_min_score' => 40.00,
            'second_round_max' => 56.00,
        ]);

        $student = $this->createStudent($this->year1, '2026101001');

        $batch = GradeBatch::create([
            'batch_code' => 'BATCH-TEST-01',
            'branch_id' => $this->branch->id,
            'academic_year_id' => $this->year->id,
            'study_year_id' => $this->year1->id,
            'department_id' => $this->department->id,
            'semester' => 1,
        ]);

        $grade = StudentGrade::create([
            'grade_batch_id' => $batch->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'daily_activities' => 4.00,
            'applications_avg' => 4.00,
            'midterm_grade' => 4.00,
            'semester_final_exam' => 25.00,
            'second_semester_final_exam' => 26.00,
            'both_semesters_grand_total' => 75.00,
        ]);

        $this->engine->calculateGrade($grade);
        $grade->save();

        $this->assertEquals(12.00, $grade->semester_work_total);
        $this->assertEquals(51.00, $grade->both_semesters_final_total); // 25 + 26
        $this->assertTrue($grade->passed_exam_rule); // 51 >= 22.40
        $this->assertTrue($grade->passed_total_rule); // 75 >= 40.00
        $this->assertEquals('PASS', $grade->status);
        $this->assertEquals('A', $grade->letter_grade); // 75 / 80 = 93.75% -> A
    }

    /**
     * Test 2: Year 1 (80-weight course) fails the 40% finals rule even with total >= 50%.
     */
    public function test_year1_80_weight_course_fails_40_percent_final_rule(): void
    {
        $course = Course::create([
            'study_year_id' => $this->year1->id,
            'department_id' => $this->department->id,
            'semester' => 1,
            'code' => 'AQD101',
            'name' => 'العقيدة',
            'credit_hours' => 2,
            'weekly_hours' => 2,
            'assessment_system' => 'SEMESTER_SYSTEM',
            'max_coursework_grade' => 12.00,
            'max_midterm_grade' => 4.00,
            'max_final_grade' => 28.00,
            'pass_grade' => 40.00,
            'max_score' => 80.00,
            'pass_min_score' => 40.00,
            'second_round_max' => 56.00,
        ]);

        $student = $this->createStudent($this->year1, '2026101002');

        $batch = GradeBatch::create([
            'batch_code' => 'BATCH-TEST-02',
            'branch_id' => $this->branch->id,
            'academic_year_id' => $this->year->id,
            'study_year_id' => $this->year1->id,
            'department_id' => $this->department->id,
            'semester' => 1,
        ]);

        // Student gets 10 in sem1 final and 10 in sem2 final = 20 total finals (below 22.40)
        // Even if both_semesters_grand_total = 44 (which is >= 40.00 pass grade)
        $grade = StudentGrade::create([
            'grade_batch_id' => $batch->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'daily_activities' => 4.00,
            'applications_avg' => 4.00,
            'midterm_grade' => 4.00,
            'semester_final_exam' => 10.00,
            'second_semester_final_exam' => 10.00,
            'both_semesters_grand_total' => 44.00,
        ]);

        $this->engine->calculateGrade($grade);
        $grade->save();

        $this->assertEquals(20.00, $grade->both_semesters_final_total);
        $this->assertFalse($grade->passed_exam_rule); // 20.00 < 22.40 (40% rule violated!)
        $this->assertTrue($grade->passed_total_rule); // 44.00 >= 40.00
        $this->assertEquals('RESIT', $grade->status); // Forced to RESIT
        $this->assertStringContainsString('لم يستوفِ شرط الـ 40%', $grade->academic_status_note);
    }

    /**
     * Test 3: Year 1 single-hour course (40-weight course) with decimals (2.5 / 11.20 threshold).
     */
    public function test_year1_40_weight_course_single_hour_and_decimals(): void
    {
        $course = Course::create([
            'study_year_id' => $this->year1->id,
            'department_id' => $this->department->id,
            'semester' => 1,
            'code' => 'HAD101',
            'name' => 'علوم الحديث',
            'credit_hours' => 1,
            'weekly_hours' => 1,
            'assessment_system' => 'SEMESTER_SYSTEM',
            'max_coursework_grade' => 6.00,
            'max_midterm_grade' => 2.00,
            'max_final_grade' => 14.00,
            'pass_grade' => 20.00,
            'max_score' => 40.00,
            'pass_min_score' => 20.00,
            'second_round_max' => 26.00,
        ]);

        $student = $this->createStudent($this->year1, '2026101003');

        $batch = GradeBatch::create([
            'batch_code' => 'BATCH-TEST-03',
            'branch_id' => $this->branch->id,
            'academic_year_id' => $this->year->id,
            'study_year_id' => $this->year1->id,
            'department_id' => $this->department->id,
            'semester' => 1,
        ]);

        $grade = StudentGrade::create([
            'grade_batch_id' => $batch->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'daily_activities' => 2.00,
            'applications_avg' => 2.00,
            'midterm_grade' => 2.00,
            'semester_final_exam' => 6.00,
            'second_semester_final_exam' => 6.00, // Total finals = 12.00 >= 11.20
            'both_semesters_grand_total' => 24.00, // Total = 24 >= 20.00
        ]);

        $this->engine->calculateGrade($grade);
        $grade->save();

        $this->assertEquals(6.00, $grade->semester_work_total);
        $this->assertEquals(12.00, $grade->both_semesters_final_total);
        $this->assertTrue($grade->passed_exam_rule); // 12 >= 11.20
        $this->assertTrue($grade->passed_total_rule); // 24 >= 20.00
        $this->assertEquals('PASS', $grade->status);
    }

    /**
     * Test 4: Year 3 Graduation Year (شهادة إتمام المرحلة) - 80-score course and 19.20 year-end rule.
     */
    public function test_year3_graduation_course_80_weight_and_year_end_exam_rule(): void
    {
        $course = Course::create([
            'study_year_id' => $this->year3->id,
            'department_id' => $this->department->id,
            'semester' => 1,
            'code' => 'QRN301',
            'name' => 'القرآن وأحكام التجويد',
            'credit_hours' => 2,
            'weekly_hours' => 2,
            'assessment_system' => 'ANNUAL_PERIODS_SYSTEM',
            'max_coursework_grade' => 10.00,
            'max_midterm_grade' => 6.00,
            'max_final_grade' => 48.00,
            'pass_grade' => 40.00,
            'max_score' => 80.00,
            'pass_min_score' => 40.00,
            'second_round_max' => 48.00,
        ]);

        $student = $this->createStudent($this->year3, '2026103001');

        $batch = GradeBatch::create([
            'batch_code' => 'BATCH-TEST-04',
            'branch_id' => $this->branch->id,
            'academic_year_id' => $this->year->id,
            'study_year_id' => $this->year3->id,
            'department_id' => $this->department->id,
            'semester' => 1,
        ]);

        // Student has full periods:
        // Period 1 = 5 + 5 + 6 = 16
        // Period 2 = 5 + 5 + 6 = 16
        // Combined periods = 32
        // But in YearEndExam gets 18.00 (below 19.20 which is 40% of 48)
        // Grand total = 32 + 18 = 50.00 (> 40.00 pass grade)
        // MUST BE RESIT due to year end exam rule!
        $grade = StudentGrade::create([
            'grade_batch_id' => $batch->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'period1_activities' => 5.00,
            'period1_written' => 5.00,
            'period1_exam' => 6.00,
            'period2_activities' => 5.00,
            'period2_written' => 5.00,
            'period2_exam' => 6.00,
            'year_end_exam' => 18.00,
        ]);

        $this->engine->calculateGrade($grade);
        $grade->save();

        $this->assertEquals(16.00, $grade->period1_total);
        $this->assertEquals(16.00, $grade->period2_total);
        $this->assertEquals(32.00, $grade->periods_combined_total);
        $this->assertEquals(50.00, $grade->final_grand_total);
        $this->assertFalse($grade->passed_exam_rule); // 18.00 < 19.20 (40% of 48)
        $this->assertTrue($grade->passed_total_rule); // 50.00 >= 40.00
        $this->assertEquals('RESIT', $grade->status);
        $this->assertStringContainsString('شرط الـ 40% لامتحان نهاية العام', $grade->academic_status_note);

        // Now if YearEndExam is 20.00 (>= 19.20):
        $grade->year_end_exam = 20.00;
        $this->engine->calculateGrade($grade);
        $grade->save();

        $this->assertTrue($grade->passed_exam_rule);
        $this->assertEquals('PASS', $grade->status);
    }

    /**
     * Test 5: Year 3 Graduation Year with 2.5 decimal values (علوم الحديث ومنهج الدعوة).
     */
    public function test_year3_graduation_course_40_weight_with_half_mark_decimals(): void
    {
        $course = Course::create([
            'study_year_id' => $this->year3->id,
            'department_id' => $this->department->id,
            'semester' => 1,
            'code' => 'DAW301',
            'name' => 'منهج الدعوة',
            'credit_hours' => 1,
            'weekly_hours' => 1,
            'assessment_system' => 'ANNUAL_PERIODS_SYSTEM',
            'max_coursework_grade' => 5.00,
            'max_midterm_grade' => 3.00,
            'max_final_grade' => 24.00,
            'pass_grade' => 20.00,
            'max_score' => 40.00,
            'pass_min_score' => 20.00,
            'second_round_max' => 24.00,
        ]);

        $student = $this->createStudent($this->year3, '2026103002');

        $batch = GradeBatch::create([
            'batch_code' => 'BATCH-TEST-05',
            'branch_id' => $this->branch->id,
            'academic_year_id' => $this->year->id,
            'study_year_id' => $this->year3->id,
            'department_id' => $this->department->id,
            'semester' => 1,
        ]);

        // Period 1: activities 2.5, written 2.5, exam 3.0 -> Total 8.0
        // Period 2: activities 2.5, written 2.5, exam 3.0 -> Total 8.0
        // Combined periods: 16.0
        // YearEndExam: 20.0 (out of 24)
        $grade = StudentGrade::create([
            'grade_batch_id' => $batch->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'period1_activities' => 2.50,
            'period1_written' => 2.50,
            'period1_exam' => 3.00,
            'period2_activities' => 2.50,
            'period2_written' => 2.50,
            'period2_exam' => 3.00,
            'year_end_exam' => 20.00,
        ]);

        $this->engine->calculateGrade($grade);
        $grade->save();

        $this->assertEquals(8.00, $grade->period1_total);
        $this->assertEquals(8.00, $grade->period2_total);
        $this->assertEquals(16.00, $grade->periods_combined_total);
        $this->assertEquals(36.00, $grade->final_grand_total);
        $this->assertTrue($grade->passed_exam_rule); // 20.00 >= 9.60
        $this->assertTrue($grade->passed_total_rule); // 36.00 >= 20.00
        $this->assertEquals('PASS', $grade->status);
        $this->assertEquals('A', $grade->letter_grade); // 36/40 = 90% -> A
    }
}
