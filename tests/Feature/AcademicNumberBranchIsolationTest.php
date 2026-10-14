<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Student;
use App\Models\StudyYear;
use App\Models\User;
use App\Services\AcademicNumberGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AcademicNumberBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AcademicSystemSeeder::class);
        User::query()->update(['must_change_password' => false]);
    }

    /**
     * الرقم الأكاديمي فريد على مستوى المنظومة كلها، لذلك يجب ألا يتجاوز مولّد الأرقام
     * حدود فرع مدير الفرع عند البحث عن آخر تسلسل مستخدم.
     */
    public function test_branch_manager_generated_number_does_not_collide_with_other_branch_students()
    {
        $year = AcademicYear::where('is_current', true)->firstOrFail();
        $dept = Department::firstOrFail();
        $studyYear = StudyYear::firstOrFail();
        $ownBranch = Branch::where('code', '01')->firstOrFail();
        $otherBranch = Branch::where('code', '02')->firstOrFail();
        $manager = User::where('email', 'manager.tip@iiis.sch.ly')->firstOrFail();

        $this->actingAs($manager);
        $service = new AcademicNumberGeneratorService;

        // أول رقم متاح للبادئة الحالية (لا يوجد طلاب بعد)
        $first = $service->generate(new Student(['gender' => 'MALE']));
        $prefix = substr($first, 0, -4);

        // طالب في فرع آخر يحمل هذا الرقم بالفعل
        Student::withoutGlobalScopes()->create($this->studentData($otherBranch, '119900000401', $dept, $studyYear, $year, $first));

        $generated = $service->generate(new Student(['gender' => 'MALE']));

        $this->assertNotSame($first, $generated, 'الرقم المولّد لمدير الفرع يصطدم برقم طالب في فرع آخر');
        $this->assertSame($prefix . '0002', $generated);
    }

    private function studentData(Branch $branch, string $nationalId, Department $dept, StudyYear $studyYear, AcademicYear $year, string $academicNumber): array
    {
        return [
            'academic_number' => $academicNumber,
            'national_id' => $nationalId,
            'branch_id' => $branch->id,
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'first_name' => 'طالب',
            'father_name' => 'أب',
            'grandfather_name' => 'جد',
            'family_name' => 'اختبار',
            'mother_name' => 'أم',
            'gender' => 'MALE',
            'birth_date' => '2005-05-01',
            'birth_place' => 'طرابلس',
            'phone' => '091-1111111',
            'guardian_phone' => '091-2222222',
            'study_type' => 'REGULAR',
            'academic_status' => 'ENROLLED_ACTIVE',
            'enrolled_academic_year_id' => $year->id,
        ];
    }
}
