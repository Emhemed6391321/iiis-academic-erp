<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\TotpService;
use App\Models\SystemAuditTrail;
use Carbon\Carbon;

class MfaController extends Controller
{
    protected TotpService $totp;

    public function __construct(TotpService $totp)
    {
        $this->totp = $totp;
    }

    /**
     * Show MFA setup screen with QR code and secret key.
     */
    public function showSetup(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasConfirmedMfa()) {
            return redirect()->intended('/');
        }

        $secret = $request->session()->get('mfa_setup_secret');
        if (!$secret) {
            $secret = $this->totp->generateSecret(32);
            $request->session()->put('mfa_setup_secret', $secret);
        }

        $otpAuthUrl = $this->totp->getOtpAuthUrl($user->email, $secret, 'IIIS ERP - ' . ($user->branch ? $user->branch->name : 'HQ'));

        return view('auth.mfa_setup', [
            'secret' => $secret,
            'otpAuthUrl' => $otpAuthUrl,
            'user' => $user,
        ]);
    }

    /**
     * Confirm MFA setup with first valid code.
     */
    public function confirmSetup(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'يرجى إدخال رمز التحقق المكون من 6 أرقام.',
            'code.size' => 'يجب أن يتكون رمز التحقق من 6 أرقام تماماً.',
        ]);

        $user = Auth::user();
        $secret = $request->session()->get('mfa_setup_secret');

        if (!$secret) {
            return redirect()->route('mfa.setup')->withErrors(['code' => 'انتهت صلاحية جلسة الإعداد، يرجى المحاولة مرة أخرى.']);
        }

        if (!$this->totp->verifyCode($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'رمز التحقق غير صحيح، تأكد من ضبط ساعة الهاتف وإدخال الرمز الحالي.']);
        }

        $recoveryCodes = $this->totp->generateRecoveryCodes(8);

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => Carbon::now(),
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
        ])->save();

        $request->session()->forget('mfa_setup_secret');
        $request->session()->put('mfa_verified', true);

        SystemAuditTrail::log(
            'MFA_SETUP_SUCCESS',
            "تم تفعيل التحقق بخطوتين (MFA/TOTP) بنجاح للمستخدم [{$user->name}].",
            ['user_id' => $user->id, 'ip' => $request->ip()]
        );

        return view('auth.mfa_recovery_codes', [
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    /**
     * Show TOTP Challenge screen during login.
     */
    public function showChallenge(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($request->session()->get('mfa_verified', false)) {
            return redirect()->intended('/');
        }

        return view('auth.mfa_challenge', [
            'user' => $user,
        ]);
    }

    /**
     * Verify TOTP code during challenge.
     */
    public function verifyChallenge(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ], [
            'code.required' => 'يرجى إدخال رمز التحقق أو أحد رموز الاسترداد.',
        ]);

        $user = Auth::user();
        $input = trim($request->input('code'));

        $isCodeValid = false;

        // Try standard 6-digit TOTP
        if (strlen($input) === 6 && ctype_digit($input) && $user->two_factor_secret) {
            $isCodeValid = $this->totp->verifyCode($user->two_factor_secret, $input);
        }

        // Try recovery codes (formatted as XXXX-XXXX)
        if (!$isCodeValid && $user->two_factor_recovery_codes) {
            $codes = json_decode($user->two_factor_recovery_codes, true) ?: [];
            $upperInput = strtoupper($input);

            if (in_array($upperInput, $codes, true)) {
                $isCodeValid = true;
                // Burn used recovery code
                $remaining = array_values(array_diff($codes, [$upperInput]));
                $user->forceFill(['two_factor_recovery_codes' => json_encode($remaining)])->save();

                SystemAuditTrail::log(
                    'MFA_RECOVERY_CODE_USED',
                    "تم استخدام رمز استرداد للطوارئ بواسطة [{$user->name}]. المتبقي: " . count($remaining),
                    ['user_id' => $user->id, 'ip' => $request->ip()]
                );
            }
        }

        if (!$isCodeValid) {
            return back()->withErrors([
                'code' => 'رمز التحقق أو رمز الاسترداد غير صحيح. تأكد من صحة المدخلات وساعة جهازك.',
            ]);
        }

        $request->session()->put('mfa_verified', true);

        SystemAuditTrail::log(
            'MFA_VERIFIED_SUCCESS',
            "تم التحقق بخطوتين بنجاح للمستخدم [{$user->name}].",
            ['user_id' => $user->id, 'ip' => $request->ip()]
        );

        return redirect()->intended('/');
    }
}
