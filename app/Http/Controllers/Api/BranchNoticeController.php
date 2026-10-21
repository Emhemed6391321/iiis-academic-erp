<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BranchNotice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * إشعارات الفرع: يرى كل مستخدم إشعارات فرعه، والإدارة العامة ترى الكل.
 */
class BranchNoticeController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $visible = BranchNotice::visibleTo($user);

        $notices = (clone $visible)->latest('id')->limit(30)->get();

        return response()->json([
            'success' => true,
            'unread_count' => (clone $visible)->whereNull('read_at')->count(),
            'notices' => $notices->map(fn (BranchNotice $notice) => [
                'id' => $notice->id,
                'title' => $notice->title,
                'body' => $notice->body,
                'read' => $notice->read_at !== null,
                'time_ago' => $notice->created_at?->diffForHumans(),
            ])->values(),
        ]);
    }

    public function markRead(int $notice): JsonResponse
    {
        $record = BranchNotice::visibleTo(Auth::user())->findOrFail($notice);

        if ($record->read_at === null) {
            $record->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllRead(): JsonResponse
    {
        BranchNotice::visibleTo(Auth::user())->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
