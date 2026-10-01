<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\GradeBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ExamApprovalController extends Controller
{
    public function getPendingBatches(): JsonResponse
    {
        $batches = GradeBatch::withoutGlobalScopes()
            ->with(['branch', 'academicYear', 'studyYear', 'department', 'submitter', 'studentGrades.course', 'studentGrades.student'])
            ->whereIn('status', ['SUBMITTED_TO_HQ', 'HQ_APPROVED'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'batches' => $batches,
            'data' => $batches,
        ]);
    }

    public function rejectBatch(Request $request, GradeBatch $batch): JsonResponse
    {
        $request->validate([
            'notes' => 'required|string|min:5',
        ]);

        $batch->update([
            'status' => 'HQ_REJECTED',
            'rejection_notes' => $request->notes,
            'reviewed_by' => Auth::id() ?? 1,
            'reviewed_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تمت إعادة دفعة الدرجات للفرع للمراجعة والتصحيح مع تدوين الملاحظات.',
            'batch' => $batch,
        ]);
    }
}
