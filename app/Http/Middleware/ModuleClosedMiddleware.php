<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\ModuleStateController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يمنع الوصول إلى واجهات وحدة مغلقة "قريباً" لجميع المستخدمين، بمن فيهم المدير العام.
 * الإغلاق هنا على مستوى الخادم حتى لا تكفي معرفة الرابط المباشر لفتح الوحدة.
 */
class ModuleClosedMiddleware
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (in_array($module, ModuleStateController::CLOSED_MODULES, true)) {
            return response()->json([
                'success' => false,
                'code' => 'MODULE_CLOSED',
                'feature' => $module,
                'message' => 'هذه الوحدة مغلقة حالياً (قريباً) ولا تتاح لأي مستخدم إلى حين افتتاحها.',
            ], 423);
        }

        return $next($request);
    }
}
