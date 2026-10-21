<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\EmployeePlacement;
use App\Models\Role;
use App\Models\User;

/**
 * أعداد الكادر الحقيقية لكل فرع، مبنية على الحسابات الفعلية للموظفين وتسكيناتهم الحالية.
 *
 * الموظف يُحسب في فرع إذا كان حسابه مرتبطاً بالفرع (users.branch_id)
 * أو لديه تسكين حالي نشط في الفرع. ويُعد "تدريسياً" إذا كان دوره عضو هيئة تدريس.
 * الأرقام اليدوية القديمة في جدول الفروع لم تعد مصدراً للعرض.
 */
class BranchStaffService
{
    /**
     * @return array<int, array{total: int, academic: int, admin: int}> مفتاحه معرّف الفرع
     */
    public static function countsByBranch(): array
    {
        $instructorRoleId = Role::where('name', 'course_instructor')->value('id');

        // branch_id => [user_id => هل هو تدريسي]
        $members = [];

        User::query()
            ->where('is_active', true)
            ->whereNotNull('branch_id')
            ->get(['id', 'branch_id', 'role_id'])
            ->each(function (User $user) use (&$members, $instructorRoleId) {
                $members[(int) $user->branch_id][(int) $user->id] = self::isInstructor($user->role_id, $instructorRoleId);
            });

        EmployeePlacement::with('user:id,role_id,is_active')
            ->where('is_current', true)
            ->where('status', 'active')
            ->whereNotNull('branch_id')
            ->get(['id', 'user_id', 'branch_id'])
            ->each(function (EmployeePlacement $placement) use (&$members, $instructorRoleId) {
                $user = $placement->user;
                if (!$user || !$user->is_active) {
                    return;
                }
                $members[(int) $placement->branch_id][(int) $user->id] ??= self::isInstructor($user->role_id, $instructorRoleId);
            });

        $counts = [];
        foreach ($members as $branchId => $people) {
            $academic = count(array_filter($people));
            $total = count($people);

            $counts[$branchId] = [
                'total'    => $total,
                'academic' => $academic,
                'admin'    => $total - $academic,
            ];
        }

        return $counts;
    }

    /**
     * أعداد فرع واحد (صفر إن لم يكن فيه كادر مسجّل).
     *
     * @return array{total: int, academic: int, admin: int}
     */
    public static function forBranch(int $branchId): array
    {
        return self::countsByBranch()[$branchId] ?? ['total' => 0, 'academic' => 0, 'admin' => 0];
    }

    /**
     * العدد المعروض للفرع: عدد الموظفين والمعلمين المُدخل عند إضافة الفرع أو تعديله.
     * وإن تُرك أي حقل فارغاً يُعرض العدد المحسوب من الحسابات والتسكينات لذلك الصنف.
     *
     * @param  array{total: int, academic: int, admin: int}  $live  الأعداد المحسوبة للفرع
     * @return array{total: int, academic: int, admin: int}
     */
    public static function effective(Branch $branch, array $live): array
    {
        $academic = $branch->operational_academic_staff ?? $live['academic'];
        $admin = $branch->operational_admin_staff ?? $live['admin'];

        return ['total' => $academic + $admin, 'academic' => $academic, 'admin' => $admin];
    }

    private static function isInstructor(?int $roleId, ?int $instructorRoleId): bool
    {
        return $instructorRoleId !== null && (int) $roleId === (int) $instructorRoleId;
    }
}
