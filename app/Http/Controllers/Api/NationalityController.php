<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nationality;
use App\Models\Student;
use App\Models\SystemAuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * إدارة الجنسيات المعتمدة للتسجيل. تُضيفها الإدارة العامة وتعدّلها، ولا تُحذف الجنسية بل تُوقف.
 */
class NationalityController extends Controller
{
    public function index(): JsonResponse
    {
        $nationalities = Nationality::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active', 'sort_order']);

        return response()->json([
            'success' => true,
            'nationalities' => $nationalities,
            'locked_name' => Student::LIBYAN_NATIONALITY,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request, null);

        $nationality = Nationality::create([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? (int) Nationality::max('sort_order') + 1,
        ]);

        $this->audit('NATIONALITY_CREATED', $nationality, [], $nationality->only(['name', 'is_active', 'sort_order']));

        return response()->json([
            'success' => true,
            'message' => "تمت إضافة الجنسية «{$nationality->name}» إلى قائمة التسجيل.",
            'nationality' => $nationality,
        ], 201);
    }

    public function update(Request $request, Nationality $nationality): JsonResponse
    {
        $validated = $this->validated($request, $nationality->id);

        $before = $nationality->only(['name', 'is_active', 'sort_order']);
        $nationality->update($validated);

        $this->audit('NATIONALITY_UPDATED', $nationality, $before, $nationality->only(['name', 'is_active', 'sort_order']));

        return response()->json([
            'success' => true,
            'message' => $nationality->is_active
                ? "تم حفظ الجنسية «{$nationality->name}»."
                : "أُوقفت الجنسية «{$nationality->name}» ولن تظهر في قوائم التسجيل الجديدة.",
            'nationality' => $nationality,
        ]);
    }

    /**
     * الاسم فريد، ولا يُسمح بـ«ليبي» لأنها مفروضة تلقائياً للطالب الليبي.
     */
    private function validated(Request $request, ?int $ignoreId): array
    {
        $request->merge([
            'name' => trim(preg_replace('/\s+/u', ' ', (string) $request->input('name', ''))),
        ]);

        return $request->validate([
            'name' => [
                $ignoreId === null ? 'required' : 'sometimes',
                'string',
                'max:100',
                Rule::notIn([Student::LIBYAN_NATIONALITY]),
                Rule::unique('nationalities', 'name')->ignore($ignoreId),
            ],
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ], [
            'name.not_in' => 'الجنسية «ليبي» ثابتة للطالب الليبي ولا تُضاف إلى هذه القائمة.',
            'name.unique' => 'هذه الجنسية موجودة في القائمة.',
        ]);
    }

    private function audit(string $eventType, Nationality $nationality, array $before, array $after): void
    {
        SystemAuditTrail::log(
            eventType: $eventType,
            description: "{$eventType}: الجنسية «{$nationality->name}» بواسطة [" . (Auth::user()?->name ?? 'النظام') . ']',
            payload: ['nationality_id' => $nationality->id, 'name' => $nationality->name],
            userId: Auth::id(),
            modelType: 'Nationality',
            modelId: $nationality->id,
            oldValues: $before,
            newValues: $after,
        );
    }
}
