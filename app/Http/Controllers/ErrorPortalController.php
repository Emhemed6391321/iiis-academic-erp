<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureErrorPortalUnlocked;
use App\Models\SystemErrorLog;
use App\Services\ErrorPromptGenerator;
use App\Services\SystemErrorMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ErrorPortalController extends Controller
{
    private const STATUSES = ['NEW', 'IN_REVIEW', 'RESOLVED', 'IGNORED'];

    public function showLogin(Request $request)
    {
        if ($request->session()->has(EnsureErrorPortalUnlocked::SESSION_KEY)) {
            return redirect()->route('error-portal.index');
        }

        return response()
            ->view('error_portal.login')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, private');
    }

    public function login(Request $request)
    {
        $key = 'error-portal:' . $request->ip();
        $max = config('error_portal.max_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $wait = RateLimiter::availableIn($key);
            return back()->withErrors(['password' => "محاولات كثيرة. أعد المحاولة بعد {$wait} ثانية."]);
        }

        $hash = config('error_portal.password_hash');
        $password = (string) $request->input('password', '');

        if ($hash === '' || $password === '' || ! password_verify($password, $hash)) {
            RateLimiter::hit($key, config('error_portal.decay_seconds', 900));
            return back()->withErrors(['password' => 'كلمة السر غير صحيحة.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put(EnsureErrorPortalUnlocked::SESSION_KEY, time());

        return redirect()->route('error-portal.index');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(EnsureErrorPortalUnlocked::SESSION_KEY);

        return redirect()->route('error-portal.login');
    }

    public function index(Request $request, SystemErrorMonitoringService $service)
    {
        $filters = $request->only(['search', 'error_type', 'severity', 'status']);
        $filters['per_page'] = 25;

        return view('error_portal.index', [
            'logs' => $service->getFilteredLogs($filters)->withQueryString(),
            'kpis' => $service->getDashboardKPIs(),
            'filters' => $filters,
            'statuses' => self::STATUSES,
        ]);
    }

    public function show(int $id, ErrorPromptGenerator $prompts)
    {
        // الجلب هنا (بعد فحص كلمة السر) لا بربط النموذج، كي لا يكشف 404 وجود السجل لغير المصرّح
        $log = SystemErrorLog::findOrFail($id);

        return view('error_portal.show', [
            'log' => $log,
            'prompt' => $prompts->generate($log),
            'statuses' => self::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, int $id, SystemErrorMonitoringService $service)
    {
        $log = SystemErrorLog::findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', self::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->updateStatus($log->id, $data['status'], $data['notes'] ?? null);

        return redirect()->route('error-portal.show', $log)->with('ok', 'تم تحديث الحالة.');
    }
}
