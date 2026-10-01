<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceMfaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->requiresMfa()) {
                $routeName = $request->route()?->getName();
                $isLogout = ($routeName === 'logout');

                // Case 1: MFA not configured yet
                if (!$user->hasConfirmedMfa()) {
                    $allowed = ['mfa.setup', 'mfa.confirm', 'logout'];
                    if (!in_array($routeName, $allowed) && !$isLogout) {
                        if ($request->expectsJson() || $request->is('api/*')) {
                            return response()->json([
                                'success' => false,
                                'code' => 'MFA_SETUP_REQUIRED',
                                'message' => 'يتطلب حسابك الإداري تفعيل التحقق بخطوتين (MFA/TOTP) إلزامياً.',
                                'redirect' => route('mfa.setup'),
                            ], 403);
                        }

                        return redirect()->route('mfa.setup');
                    }
                }
                // Case 2: MFA configured, but not verified in this session
                elseif (!$request->session()->get('mfa_verified', false)) {
                    $allowed = ['mfa.challenge', 'mfa.verify', 'logout'];
                    if (!in_array($routeName, $allowed) && !$isLogout) {
                        if ($request->expectsJson() || $request->is('api/*')) {
                            return response()->json([
                                'success' => false,
                                'code' => 'MFA_CHALLENGE_REQUIRED',
                                'message' => 'يرجى إدخال رمز التحقق بخطوتين (TOTP) لتأكيد هويتك.',
                                'redirect' => route('mfa.challenge'),
                            ], 403);
                        }

                        return redirect()->route('mfa.challenge');
                    }
                }
            }
        }

        return $next($request);
    }
}
