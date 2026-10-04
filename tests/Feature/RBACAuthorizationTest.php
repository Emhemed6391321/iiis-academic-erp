<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Branch;
use App\Models\Department;
use App\Models\StudyYear;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\SystemAuditTrail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RBACAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_auth_me_endpoint_returns_comprehensive_rbac_context()
    {
        $branch = Branch::first() ?? Branch::create(['name' => 'فرع طرابلس', 'code' => 'BR_01', 'city' => 'طرابلس', 'is_active' => true]);
        $role = Role::where('name', 'branch_registrar')->first();
        
        $user = User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'role',
                'scope',
                'branch_id',
                'permissions',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'is_super_admin',
                    'branch_id',
                    'scope',
                    'permissions',
                ],
            ]);

        $this->assertEquals('branch_registrar', $response->json('role'));
        $this->assertEquals('BRANCH', $response->json('scope'));
        $this->assertEquals($branch->id, $response->json('branch_id'));
        $this->assertIsArray($response->json('permissions'));
        $this->assertContains('students.view', $response->json('permissions'));
    }

    public function test_super_admin_has_unrestricted_access_to_all_guarded_endpoints()
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'branch_id' => null,
        ]);

        $this->actingAs($superAdmin);

        // Permissions Matrix
        $this->getJson('/api/v1/permissions/matrix')->assertStatus(200);

        // Admin Settings
        $this->getJson('/api/v1/admin/settings/all')->assertStatus(200);

        // System Monitoring / Errors
        $this->getJson('/api/v1/system-errors')->assertStatus(200);

        // Audit Logs (System trails)
        $this->getJson('/api/v1/audit/system-trails')->assertStatus(200);
    }

    public function test_unauthorized_user_is_forbidden_with_403_and_forensically_audited()
    {
        $branch = Branch::first() ?? Branch::create(['name' => 'فرع بنغازي', 'code' => 'BR_02', 'city' => 'بنغازي', 'is_active' => true]);
        
        // Create custom role with no permissions
        $restrictedRole = Role::create([
            'name' => 'guest_officer',
            'display_name' => 'موظف زائر',
            'scope_type' => 'BRANCH_SCOPE',
            'is_system' => false,
        ]);

        $user = User::factory()->create([
            'role_id' => $restrictedRole->id,
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($user);

        // Attempting to access sensitive admin matrix without permissions
        $response = $this->getJson('/api/v1/permissions/matrix');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'required_permission' => 'permissions.manage|MANAGE_ROLES',
            ]);

        // Verify forensic audit trail logged this unauthorized attempt
        $auditLog = SystemAuditTrail::where('event_type', 'UNAUTHORIZED_ACCESS_ATTEMPT')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($auditLog, 'Unauthorized access attempt must be registered in SystemAuditTrail');
        $this->assertEquals('UNAUTHORIZED_ACCESS_ATTEMPT', $auditLog->event_type);
        $this->assertStringContainsString('محاولة وصول غير مصرح بها', $auditLog->description);
    }

    public function test_branch_scope_isolation_prevents_cross_branch_data_access()
    {
        $branches = Branch::take(2)->get();
        if ($branches->count() >= 2) {
            $branch1 = $branches[0];
            $branch2 = $branches[1];
        } else {
            $branch1 = Branch::create(['name' => 'فرع تجريبي 1', 'code' => 'TBR_1', 'city' => 'طرابلس', 'is_active' => true]);
            $branch2 = Branch::create(['name' => 'فرع تجريبي 2', 'code' => 'TBR_2', 'city' => 'بنغازي', 'is_active' => true]);
        }
        
        $dept = Department::first() ?? Department::create(['name' => 'الحديث', 'code' => 'HD']);
        $studyYear = StudyYear::first() ?? StudyYear::create(['name' => 'السنة الأولى', 'year_number' => 1]);
        $academicYear = AcademicYear::where('is_current', true)->first() ?? AcademicYear::create(['name' => '2026/2027', 'code' => '2026', 'is_current' => true]);

        $studentBranch1 = Student::withoutGlobalScopes()->create([
            'academic_number' => '3260001',
            'national_id' => '119950000031',
            'first_name' => 'طارق',
            'father_name' => 'علي',
            'grandfather_name' => 'صالح',
            'family_name' => 'المصراتي',
            'mother_name' => 'فاطمة سالم',
            'birth_place' => 'مصراتة',
            'gender' => 'MALE',
            'birth_date' => '1995-01-01',
            'nationality' => 'ليبي',
            'phone' => '091-0000001',
            'guardian_phone' => '092-0000001',
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'study_type' => 'REGULAR',
            'branch_id' => $branch1->id,
            'enrolled_academic_year_id' => $academicYear->id,
            'academic_status' => 'ENROLLED_ACTIVE',
        ]);

        $studentBranch2 = Student::withoutGlobalScopes()->create([
            'academic_number' => '4260001',
            'national_id' => '119950000041',
            'first_name' => 'سالم',
            'father_name' => 'محمود',
            'grandfather_name' => 'عثمان',
            'family_name' => 'الزاوي',
            'mother_name' => 'مريم مسعود',
            'birth_place' => 'الزاوية',
            'gender' => 'MALE',
            'birth_date' => '1995-02-02',
            'nationality' => 'ليبي',
            'phone' => '091-0000002',
            'guardian_phone' => '092-0000002',
            'department_id' => $dept->id,
            'current_study_year_id' => $studyYear->id,
            'study_type' => 'REGULAR',
            'branch_id' => $branch2->id,
            'enrolled_academic_year_id' => $academicYear->id,
            'academic_status' => 'ENROLLED_ACTIVE',
        ]);

        $registrarRole = Role::where('name', 'branch_registrar')->first();
        $branch1User = User::factory()->create([
            'role_id' => $registrarRole->id,
            'branch_id' => $branch1->id,
        ]);

        $this->actingAs($branch1User);

        // When requesting students index, only Branch 1 students must be returned
        $response = $this->getJson('/api/v1/students');
        $response->assertStatus(200);

        $studentIds = collect($response->json('data.data'))->pluck('id')->toArray();
        $this->assertContains($studentBranch1->id, $studentIds);
        $this->assertNotContains($studentBranch2->id, $studentIds, 'Branch 1 user must NEVER see Branch 2 students');

        // Direct access to Branch 2 student should return 404 due to BranchScope
        $detailResponse = $this->getJson('/api/v1/students/' . $studentBranch2->id);
        $detailResponse->assertStatus(404);
    }

    public function test_route_middleware_blocks_sensitive_academic_actions_without_specific_permission()
    {
        $branch = Branch::first() ?? Branch::create(['name' => 'فرع طرابلس', 'code' => 'BR_03', 'city' => 'طرابلس', 'is_active' => true]);

        // Role with ONLY students.view, but NOT students.create
        $viewerRole = Role::create([
            'name' => 'student_reader',
            'display_name' => 'قارئ بيانات الطلاب',
            'scope_type' => 'BRANCH_SCOPE',
            'is_system' => false,
        ]);
        $viewPerm = Permission::where('code', 'students.view')->first();
        if ($viewPerm) {
            $viewerRole->permissions()->attach($viewPerm->id);
        }

        $user = User::factory()->create([
            'role_id' => $viewerRole->id,
            'branch_id' => $branch->id,
        ]);

        $this->actingAs($user);

        // GET students is authorized
        $this->getJson('/api/v1/students')->assertStatus(200);

        // POST students (create) is forbidden
        $this->postJson('/api/v1/students', [
            'first_name' => 'تجربة',
        ])->assertStatus(403)
          ->assertJson([
              'required_permission' => 'students.create',
          ]);
    }
}
