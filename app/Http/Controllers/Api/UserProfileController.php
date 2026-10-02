<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    /**
     * Get authenticated user profile details.
     */
    public function getProfile(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }

        $user->load(['role.permissions', 'branch']);

        $recentActivities = \App\Models\SystemAuditTrail::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->latest('id')
            ->take(8)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event_type' => $log->event_type,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at ? $log->created_at->diffForHumans() : '',
                ];
            });

        $permissions = $user->role ? $user->role->permissions->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'display_name' => $p->display_name ?? $p->name,
                'category' => $p->category ?? 'عام',
            ];
        }) : [];

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'national_id' => $user->national_id ?? '',
                'role_name' => $user->role?->display_name ?? 'مستخدم إداري',
                'role_code' => $user->role?->name ?? 'user',
                'scope_type' => $user->role?->scope_type ?? 'GLOBAL_SCOPE',
                'branch_name' => $user->branch?->name ?? 'الإدارة المركزية العامة',
                'created_at' => $user->created_at?->format('Y-m-d') ?? '',
                'last_login_at' => $user->last_login_at?->toIso8601String() ?? null,
                'last_login_ip' => $user->last_login_ip ?? '127.0.0.1',
                'two_factor_enabled' => (bool)$user->two_factor_enabled,
            ],
            'permissions' => $permissions,
            'recent_activities' => $recentActivities,
        ]);
    }

    /**
     * Toggle two-factor authentication.
     */
    public function toggleTwoFactor(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }

        $user->two_factor_enabled = !$user->two_factor_enabled;
        if ($user->two_factor_enabled) {
            $user->two_factor_confirmed_at = now();
        } else {
            $user->two_factor_confirmed_at = null;
        }
        $user->save();

        \App\Models\SystemAuditTrail::create([
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'event_type' => 'SECURITY_2FA_TOGGLED',
            'description' => $user->two_factor_enabled 
                ? 'تم تفعيل المصادقة الثنائية (2FA) لحساب المستخدم.' 
                : 'تم تعطيل المصادقة الثنائية (2FA) لحساب المستخدم.',
            'ip_address' => $request->ip(),
            'payload' => ['two_factor_enabled' => $user->two_factor_enabled],
        ]);

        return response()->json([
            'success' => true,
            'message' => $user->two_factor_enabled ? 'تم تفعيل المصادقة الثنائية وتأمين حسابك بنجاح.' : 'تم تعطيل المصادقة الثنائية للحساب.',
            'two_factor_enabled' => (bool)$user->two_factor_enabled,
        ]);
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'national_id' => 'nullable|string|max:20',
        ], [
            'name.required' => 'الاسم الكامل مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل بحساب آخر.',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? $user->phone;
        $user->national_id = $validated['national_id'] ?? $user->national_id;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ وتحديث بيانات الملف الشخصي بنجاح.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'national_id' => $user->national_id,
            ],
        ]);
    }

    /**
     * Update user account password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 8 خانات.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة.',
            ], 422);
        }

        $user->password = $validated['password'];
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث كلمة المرور بنجاح تام وتأمين الحساب.',
        ]);
    }
}
