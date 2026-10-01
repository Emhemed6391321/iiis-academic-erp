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
    /**
     * Maximum login attempts before lockout.
     */
    protected int $maxAttempts = 5;

    /**
     * Lockout duration in minutes.
     */
    protected int $decayMinutes = 1;

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

        $throttleKey = $this->throttleKey($request);

        // 1. Check Rate Limiter (Brute-force protection)
        if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->logAuditAttempt($request, null, 'LOCKED_OUT', "تجاوز محاولات الدخول المسموح بها. حظر مؤقت لمدة {$seconds} ثانية.");

            return back()->withErrors([
                'email' => "تم حظر المحاولات مؤقتاً لحماية الحساب. يرجى الانتظار {$seconds} ثانية قبل إعادة المحاولة.",
            ])->onlyInput('email');
        }

        // 2. Find User by email or national_id
        $loginInput = trim($credentials['email']);
        $user = User::where('email', $loginInput)
            ->orWhere('national_id', $loginInput)
            ->first();

        // 3. Verify user existence and password
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, $this->decayMinutes * 60);

            $this->logAuditAttempt($request, $user ? $user->id : null, 'FAILED_CREDENTIALS', 'محاولة تسجيل دخول فاشلة ببيانات غير صحيحة.');

            $attemptsLeft = RateLimiter::remaining($throttleKey, $this->maxAttempts);

            return back()->withErrors([
                'email' => "بيانات الاعتماد غير صحيحة. المتبقي: {$attemptsLeft} محاولات قبل الإغلاق الأمني المؤقت.",
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
        RateLimiter::clear($throttleKey);

        $remember = $request->boolean('remember');
        Auth::login($user, $remember);

        // Prevent session fixation attack
        $request->session()->regenerate();

        // Update user's last login metadata
        $user->update([
            'last_login_at' => Carbon::now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Forensic audit logging
        $this->logAuditAttempt($request, $user->id, 'LOGIN_SUCCESS', 'تسجيل دخول آمن وناجح للمنظومة.');

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
