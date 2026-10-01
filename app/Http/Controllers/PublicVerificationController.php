<?php

namespace App\Http\Controllers;

use App\Services\DocumentLedgerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PublicVerificationController extends Controller
{
    /**
     * Public verification endpoint for official institute documents.
     * Accessible without login by external entities, employers, and government portals.
     */
    public function show(string $uuid, Request $request, DocumentLedgerService $ledgerService): View|JsonResponse
    {
        $result = $ledgerService->verifyDocument($uuid);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => $result['is_valid'],
                'data'    => $result,
            ], $result['exists'] ? 200 : 404);
        }

        return view('verify_document', [
            'uuid'   => $uuid,
            'result' => $result,
        ]);
    }
}
