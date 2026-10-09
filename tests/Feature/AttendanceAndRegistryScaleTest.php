<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\User;
use App\Services\AbsenceCalculationEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceAndRegistryScaleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        User::query()->update(['must_change_password' => false]);
        DB::table('students')->delete();
        $this->admin = User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->first();

        $branch = Branch::value('id');
        $year = AcademicYear::where('is_current', true)->value('id') ?? AcademicYear::value('id');
        $rows = [];
        for ($i = 1; $i <= 120; $i++) {
            $rows[] = [
                'academic_number' => 'T' . $i,
                'national_id' => '1' . str_pad((string) $i, 11, '0', STR_PAD_LEFT),
                'branch_id' => $branch,
                'department_id' => Department::value('id'),
                'current_study_year_id' => StudyYear::value('id'),
                'enrolled_academic_year_id' => $year,
                'first_name' => 'اسم' . $i,
                'father_name' => 'أب',
                'grandfather_name' => 'جد',
                'family_name' => 'عائلة',
                'mother_name' => 'أم',
                'gender' => 'MALE',
                'birth_date' => '2005-01-01',
                'birth_place' => 'طرابلس',
                'phone' => '0911111111',
                'guardian_phone' => '0922222222',
                'study_type' => 'REGULAR',
                'academic_status' => 'ENROLLED_ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('students')->insert($rows);
    }

    private function markAbsent(int $studentId, int $days, string $status = 'ABSENT'): void
    {
        $student = DB::table('students')->find($studentId);
        $rows = [];
        for ($d = 0; $d < $days; $d++) {
            $rows[] = [
                'student_id' => $studentId,
                'branch_id' => $student->branch_id,
                'academic_year_id' => $student->enrolled_academic_year_id,
                'record_date' => now()->subDays($d)->toDateString(),
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('student_attendance')->insert($rows);
    }

    public function test_at_risk_endpoint_uses_bounded_queries_and_matches_engine(): void
    {
        $ids = DB::table('students')->pluck('id')->all();
        foreach (array_slice($ids, 0, 40) as $n => $id) {
            $this->markAbsent($id, 4 + ($n % 12));
        }

        DB::enableQueryLog();
        $res = $this->actingAs($this->admin)->getJson('/api/v1/attendance/at-risk-students?min_absent_days=3');
        $queries = count(DB::getQueryLog());

        $res->assertOk()->assertJsonPath('total_at_risk', 40)->assertJsonPath('truncated', false);
        $this->assertLessThan(30, $queries, "at-risk endpoint ran {$queries} queries for 40 students");

        $engine = app(AbsenceCalculationEngineService::class);
        foreach ($res->json('students') as $row) {
            $expected = $engine->evaluateStudentAbsence(Student::find($row['student_id']));
            $this->assertEquals($expected['absence_percentage'], $row['absence_percentage']);
            $this->assertSame($expected['warning_level'], $row['suggested_level']);
        }
    }

    public function test_at_risk_endpoint_honours_limit_and_reports_truncation(): void
    {
        foreach (array_slice(DB::table('students')->pluck('id')->all(), 0, 10) as $id) {
            $this->markAbsent($id, 5);
        }

        $this->actingAs($this->admin)->getJson('/api/v1/attendance/at-risk-students?limit=3')
            ->assertOk()
            ->assertJsonPath('returned', 3)
            ->assertJsonPath('total_at_risk', 10)
            ->assertJsonPath('truncated', true);
    }

    public function test_chronic_absence_report_type_works(): void
    {
        $this->markAbsent(DB::table('students')->value('id'), 6);

        $this->actingAs($this->admin)->getJson('/api/v1/attendance/reports?report_type=CHRONIC_ABSENCE')->assertOk();
    }

    public function test_registry_full_name_search_uses_portable_concatenation(): void
    {
        $res = $this->actingAs($this->admin)->getJson('/api/v1/students/registry?search=' . urlencode('اسم7 أب جد'));

        $res->assertOk();
        $this->assertSame(1, $res->json('pagination.total'));
    }

    public function test_registry_export_contains_all_rows_and_honours_json_columns(): void
    {
        $columns = json_encode(['academic_number', 'full_name', 'national_id']);
        $res = $this->actingAs($this->admin)->get('/api/v1/students/registry/export?columns=' . urlencode($columns));

        $res->assertOk();
        ob_start();
        $res->baseResponse->sendContent();
        $csv = ob_get_clean();
        $lines = array_filter(explode("\n", trim($csv)));

        $this->assertCount(121, $lines, 'header + all 120 students, not just the first page of 50');
        $this->assertStringContainsString('الرقم الأكاديمي', $lines[0]);
        $this->assertStringNotContainsString('[', $lines[0]);
    }
}
