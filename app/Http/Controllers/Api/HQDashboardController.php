<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MetricsCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HQDashboardController extends Controller
{
    public function __construct(
        protected MetricsCacheService $metricsCache
    ) {}

    public function getSummary(Request $request): JsonResponse
    {
        $forceRefresh = $request->boolean('refresh', false);
        $data = $this->metricsCache->getHQSummary($forceRefresh);

        return response()->json(array_merge([
            'success' => true,
        ], $data));
    }
}
