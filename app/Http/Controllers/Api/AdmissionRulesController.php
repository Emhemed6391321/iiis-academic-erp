<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdmissionRulesService;
use App\Services\AdminSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdmissionRulesController extends Controller
{
    public function show(AdmissionRulesService $rules): JsonResponse
    {
        return response()->json([
            'success' => true,
            'rules' => $rules->rules(),
            'cities_with_female_regular_institute' => $rules->citiesWithFemaleInstitute(),
            'reference_date' => $rules->referenceDate()->toDateString(),
            'defaults' => AdmissionRulesService::defaultRules(),
        ]);
    }

    public function save(Request $request, AdmissionRulesService $rules): JsonResponse
    {
        $validated = $request->validate([
            'rules' => 'required|array',
            'cities_with_female_regular_institute' => 'nullable|array',
            'cities_with_female_regular_institute.*' => 'string|max:100',
            'reference_date' => 'nullable|date',
        ]);

        $saved = $rules->saveRules(
            $validated['rules'],
            $validated['cities_with_female_regular_institute'] ?? null,
            Auth::id()
        );

        if ($request->has('reference_date')) {
            AdminSettingsService::setSetting(
                AdmissionRulesService::REFERENCE_DATE_KEY,
                $validated['reference_date'] ?? '',
                'admissions',
                'التاريخ المرجعي لحساب العمر',
                'string',
                Auth::id()
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ قواعد القبول بحسب العمر في الإعدادات المركزية.',
            'rules' => $saved,
            'reference_date' => $rules->referenceDate()->toDateString(),
        ]);
    }
}
