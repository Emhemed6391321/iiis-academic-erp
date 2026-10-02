<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class LoginController extends Controller
{
    protected \App\Services\LoginSecurityService $securityService;

    public function __construct(\App\Services\LoginSecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Show the official login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return view('auth.login');
    }

    /**
     * Handle an authentication attempt with enterprise security standards.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'يرجى إدخال البريد الإلكتروني أو اسم المستخدم المعتمد.',
            'password.required' => 'يرجى إدخال كلمة المرور الرسمية.',
        ]);

        $ip = $request->ip();
        $loginInput = trim($credentials['email']);

        // 1. Check Progressive Lockout (Per-account and Per-IP)
        $lockout = $this->securityService->checkLockout($loginInput, $ip);
        if ($lockout['is_locked']) {
            $this->logAuditAttempt($request, null, 'LOCKED_OUT', $lockout['message']);

            return back()->withErrors([
                'email' => $lockout['message'],
            ])->onlyInput('email');
        }

        // 2. Find User by email or national_id
        $user = User::where('email', $loginInput)
            ->orWhere('national_id', $loginInput)
            ->first();

        // 3. Verify user existence and password
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $this->securityService->recordFailedAttempt($loginInput, $ip);

            $this->logAuditAttempt($request, $user ? $user->id : null, 'FAILED_CREDENTIALS', 'محاولة تسجيل دخول فاشلة ببيانات غير صحيحة.');

            // Re-check lockout in case this attempt tripped the threshold
            $postLockout = $this->securityService->checkLockout($loginInput, $ip);
            if ($postLockout['is_locked']) {
                return back()->withErrors([
                    'email' => $postLockout['message'],
                ])->onlyInput('email');
            }

            return back()->withErrors([
                'email' => 'بيانات الاعتماد غير صحيحة. يرجى التحقق من صحة البريد وكلمة المرور.',
            ])->onlyInput('email');
        }

        // 4. Check if account is active
        if (!$user->is_active) {
            $this->logAuditAttempt($request, $user->id, 'ACCOUNT_INACTIVE', 'محاولة دخول بحساب معطل أو موقوف إدارياً.');

            return back()->withErrors([
                'email' => 'عفواً، تم إيقاف أو تعطيل هذا الحساب من قبل إدارة المنظومة. يرجى مراجعة مكتب المعلومات والتوثيق.',
            ])->onlyInput('email');
        }

        // 5. Successful Authentication
        $this->securityService->clearAttempts($loginInput, $ip);

        $remember = $request->boolean('remember');
        Auth::login($user, $remember);

        // Prevent session fixation attack
        $request->session()->regenerate();
        $request->session()->put('last_user_activity', time());

        // Update user's last login metadata
        $user->update([
            'last_login_at' => Carbon::now(),
            'last_login_ip' => $ip,
        ]);

        // Forensic audit logging
        $this->logAuditAttempt($request, $user->id, 'LOGIN_SUCCESS', 'تسجيل دخول آمن وناجح للمنظومة.');

        // 6. Mandatory MFA enforcement for users who enabled it OR required by policy
        if ($user->hasConfirmedMfa() || $user->requiresMfa()) {
            if (!$user->hasConfirmedMfa()) {
                return redirect()->route('mfa.setup');
            }
            // Require 2FA challenge
            $request->session()->put('mfa_verified', false);
            return redirect()->route('mfa.challenge');
        }

        // 7. Mandatory first-time password change
        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended('/');
    }

    /**
     * Log the user out of the application securely.
     */
    public function logout(Request $request)
    {
        $userId = Auth::id();
        if ($userId) {
            $this->logAuditAttempt($request, $userId, 'LOGOUT', 'تسجيل خروج آمن وإبطال جلسة العمل.');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('status', 'تم تسجيل الخروج الآمن بنجاح.');
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());
    }

    /**
     * Log authentication activities to system audit trails.
     */
    protected function logAuditAttempt(Request $request, ?int $userId, string $action, string $details): void
    {
        try {
            DB::table('system_audit_trails')->insert([
                'user_id'    => $userId,
                'event_type' => $action,
                'description'=> $details,
                'ip_address' => $request->ip(),
                'payload'    => json_encode([
                    'user_agent' => substr((string)$request->userAgent(), 0, 255),
                    'action'     => $action,
                ]),
                'created_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            // Ignore if table/column differences exist to prevent breaking auth flow
        }
    }
}
