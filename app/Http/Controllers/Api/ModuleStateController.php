<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * تفعيل وتعطيل وحدات المنظومة (الأقسام المقفلة بصيغة "قريباً").
 *
 * المدير العام يرى كل الوحدات دائماً ويستطيع قفل أي وحدة أو فتحها لبقية المستخدمين.
 * القائمة المقفلة تُحفظ في الإعدادات الإدارية، ويُعاد الوضع الافتراضي إن لم تُضبط بعد.
 */
class ModuleStateController extends Controller
{
    public const SETTING_KEY = 'modules.locked_sections';

    /** الوحدات التي يمكن تفعيلها أو تعطيلها من المدير العام */
    public const TOGGLEABLE_MODULES = [
        'study_and_exams' => 'الدراسة والامتحانات',
        'grading' => 'الدرجات',
        'transcripts' => 'الشهادات والوثائق والتحقق',
        'batch_print' => 'الطباعة والتصدير',
    ];

    /** الوضع الافتراضي عند عدم ضبط الإعداد: هذه الوحدات مقفلة لبقية المستخدمين */
    public const DEFAULT_LOCKED = ['study_and_exams', 'grading', 'transcripts', 'batch_print'];

    /**
     * وحدات الدراسة والامتحانات والكنترول مغلقة بالكامل حالياً "قريباً" — لجميع المستخدمين بمن فيهم المدير العام.
     * لا يُغيَّر هذا الوضع من الواجهة؛ يُعاد فتحها لاحقاً بتعديل هذا الثابت عند الافتتاح.
     */
    public const CLOSED_MODULES = ['study_and_exams', 'grading', 'transcripts', 'batch_print'];

    /**
     * الوحدات المقفلة حالياً (مرتبة حسب ترتيب الكتالوج).
     */
    public static function lockedSections(): array
    {
        return array_values(array_intersect(array_keys(self::TOGGLEABLE_MODULES), self::CLOSED_MODULES));
    }

    public function index(): JsonResponse
    {
        return $this->payload();
    }

    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'تفعيل الوحدات وتعطيلها محصور بالمدير العام.',
            ], 403);
        }

        // الوحدات مغلقة حالياً بالكامل: لا يُقبل أي تغيير في حالتها قبل إعلان الافتتاح
        return response()->json([
            'success' => false,
            'message' => 'وحدات الدراسة والامتحانات والكنترول مغلقة بالكامل حالياً (قريباً) ولا يمكن تفعيلها الآن.',
            'locked' => self::lockedSections(),
        ], 423);
    }

    private function payload(?string $message = null): JsonResponse
    {
        $locked = self::lockedSections();

        $modules = [];
        foreach (self::TOGGLEABLE_MODULES as $key => $label) {
            $modules[] = ['key' => $key, 'label' => $label, 'locked' => in_array($key, $locked, true)];
        }

        return response()->json(array_filter([
            'success' => true,
            'message' => $message,
            'locked' => $locked,
            'modules' => $modules,
        ], fn ($v) => $v !== null));
    }
}
