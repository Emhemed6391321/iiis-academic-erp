<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\SystemAuditTrail;
use Carbon\Carbon;

class PasswordChangeController extends Controller
{
    /**
     * Show mandatory or voluntary password change form.
     */
    public function showChangeForm()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        return view('auth.password_change', [
            'user' => $user,
            'isMandatory' => (bool) $user->must_change_password,
        ]);
    }

    /**
     * Update user password with enterprise complexity standards.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'password' => [
                'required',
                'string',
                'min:10',
                'confirmed',
                'regex:/[a-z]/',      // lower
                'regex:/[A-Z]/',      // upper
                'regex:/[0-9]/',      // digit
                'regex:/[@$!%*#?&^_-]/', // symbol
            ],
        ];

        // If not mandatory first change, verify current password
        if (!$user->must_change_password) {
            $rules['current_password'] = 'required|string';
        }

        $messages = [
            'password.required' => 'يرجى إدخال كلمة المرور الجديدة.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 10 خانات لضمان الأمان.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق مع الكلمة المدخلة.',
            'password.regex' => 'يجب أن تحتوي كلمة المرور على أحرف كبيرة وصغيرة وأرقام ورموز خاصة (@$!%*#?&^_-).',
            'current_password.required' => 'يرجى إدخال كلمة المرور الحالية.',
        ];

        $request->validate($rules, $messages);

        if (!$user->must_change_password) {
            if (!Hash::check($request->input('current_password'), $user->password)) {
                return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
            }
        }

        // Prevent reusing the same password
        if (Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'لا يمكن استخدام نفس كلمة المرور السابقة. اختر كلمة مرور مختلفة وقوية.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => false,
            'password_changed_at' => Carbon::now(),
        ])->save();

        SystemAuditTrail::log(
            'PASSWORD_CHANGED',
            "تم تغيير وتحديث كلمة المرور بنجاح للمستخدم [{$user->name}].",
            ['user_id' => $user->id, 'ip' => $request->ip()]
        );

        return redirect()->intended('/')->with('success', 'تم تحديث كلمة المرور بنجاح وأمان.');
    }
}
