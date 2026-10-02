<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemChangelog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemChangelogController extends Controller
{
    /**
     * Get paginated changelogs (public view - all published).
     */
    public function index(Request $request): JsonResponse
    {
        $query = SystemChangelog::published()
            ->orderByDesc('deployed_at')
            ->orderByDesc('id');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('version', 'like', "%{$search}%");
            });
        }

        $changelogs = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $changelogs->items(),
            'meta' => [
                'current_page' => $changelogs->currentPage(),
                'last_page'    => $changelogs->lastPage(),
                'total'        => $changelogs->total(),
            ],
            'stats' => [
                'total'    => SystemChangelog::published()->count(),
                'features' => SystemChangelog::published()->where('type', 'feature')->count(),
                'fixes'    => SystemChangelog::published()->where('type', 'fix')->count(),
                'security' => SystemChangelog::published()->where('type', 'security')->count(),
                'latest_version' => SystemChangelog::published()->orderByDesc('deployed_at')->value('version') ?? '1.0.0',
            ],
        ]);
    }

    /**
     * Create a new changelog entry (admin only - or from deploy script).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version'            => 'required|string|max:20',
            'title'              => 'required|string|max:255',
            'description'        => 'required|string',
            'type'               => 'required|in:feature,fix,security,performance,ui,breaking',
            'impact'             => 'nullable|in:low,medium,high,critical',
            'author'             => 'nullable|string|max:100',
            'commit_hash'        => 'nullable|string|max:64',
            'branch'             => 'nullable|string|max:100',
            'affected_modules'   => 'nullable|array',
            'tags'               => 'nullable|array',
            'is_published'       => 'nullable|boolean',
            'requires_migration' => 'nullable|boolean',
            'deployed_at'        => 'nullable|date',
        ]);

        if (empty($validated['author'])) {
            $validated['author'] = Auth::user()?->name ?? 'نظام النشر التلقائي';
        }

        if (empty($validated['deployed_at'])) {
            $validated['deployed_at'] = now();
        }

        $changelog = SystemChangelog::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة سجل الإصدار بنجاح.',
            'data'    => $changelog,
        ], 201);
    }

    /**
     * Update a changelog entry.
     */
    public function update(Request $request, SystemChangelog $changelog): JsonResponse
    {
        $validated = $request->validate([
            'version'            => 'sometimes|string|max:20',
            'title'              => 'sometimes|string|max:255',
            'description'        => 'sometimes|string',
            'type'               => 'sometimes|in:feature,fix,security,performance,ui,breaking',
            'impact'             => 'nullable|in:low,medium,high,critical',
            'is_published'       => 'nullable|boolean',
            'requires_migration' => 'nullable|boolean',
        ]);

        $changelog->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث سجل الإصدار.',
            'data'    => $changelog->fresh(),
        ]);
    }

    /**
     * Delete a changelog entry.
     */
    public function destroy(SystemChangelog $changelog): JsonResponse
    {
        $changelog->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف سجل الإصدار.',
        ]);
    }
}
