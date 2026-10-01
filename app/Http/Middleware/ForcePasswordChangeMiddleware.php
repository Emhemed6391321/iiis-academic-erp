<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChangeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->must_change_password) {
                // Allowed routes during mandatory password change
                $allowedRoutes = ['password.change', 'password.update', 'logout'];

                if (!in_array($request->route()?->getName(), $allowedRoutes)) {
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'success' => false,
                            'code' => 'PASSWORD_CHANGE_REQUIRED',
                            'message' => 'يجب تغيير كلمة المرور المؤقتة فوراً قبل متابعة استخدام المنظومة.',
                            'redirect' => route('password.change'),
                        ], 403);
                    }

                    return redirect()->route('password.change')->with('warning', 'يرجى تعيين كلمة مرور جديدة وخاصة بحسابك للاستمرار.');
                }
            }
        }

        return $next($request);
    }
}
