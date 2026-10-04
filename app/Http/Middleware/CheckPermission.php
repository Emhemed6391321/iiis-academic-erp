<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SystemAuditTrail;
use Carbon\Carbon;

class CheckPermission
{
    /**
     * Handle an incoming request and enforce RBAC permissions.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission Pipe-delimited list of acceptable permission codes (e.g. 'students.view|students.create')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح: يجب تسجيل الدخول للوصول إلى هذا المورد.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if user has permission (SuperAdmin automatically passes)
        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        // Forensic Logging for unauthorized access attempts (Defense-in-Depth)
        try {
            SystemAuditTrail::create([
                'user_id'     => $user->id,
                'branch_id'   => $user->branch_id,
                'event_type'  => 'UNAUTHORIZED_ACCESS_ATTEMPT',
                'description' => "محاولة وصول غير مصرح بها للمسار [{$request->path()}] - الصلاحية المطلوبة [{$permission}]",
                'ip_address'  => $request->ip(),
                'payload'     => [
                    'url'                 => $request->fullUrl(),
                    'method'              => $request->method(),
                    'required_permission' => $permission,
                    'user_role'           => $user->role ? $user->role->name : null,
                    'user_scope'          => $user->hasGlobalAccessScope() ? 'GLOBAL_SCOPE' : 'BRANCH_SCOPE',
                    'branch_id'           => $user->branch_id,
                ],
                'created_at'  => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // Never break response if logging table fails
        }

        return response()->json([
            'success'             => false,
            'message'             => "غير مصرح: ليس لديك الصلاحية الكافية لتنفيذ هذا الإجراء [{$permission}].",
            'error'               => "غير مصرح: ليس لديك الصلاحية الكافية لتنفيذ هذا الإجراء [{$permission}].",
            'required_permission' => $permission,
        ], 403);
    }
}
