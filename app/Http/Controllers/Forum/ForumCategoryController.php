<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Forum\ForumCategory;
use App\Models\Forum\ForumHashtag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForumCategoryController extends Controller
{
    /**
     * List all categories with hashtags and post counts
     */
    public function index(): JsonResponse
    {
        $categories = ForumCategory::with('hashtags')
            ->withCount(['posts' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => (string) $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'type' => $category->type,
                    'icon' => $category->icon,
                    'hashtags' => $category->hashtags->pluck('name')->toArray(),
                    'postCount' => $category->posts_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Create a new category (admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:100|unique:forum_categories,name',
            'description' => 'required|string|min:10|max:500',
            'type' => 'required|in:academic-qa,general-discussion',
            'hashtags' => 'nullable|array',
            'hashtags.*' => 'string|max:100',
        ], [
            'name.required' => 'Category name is required',
            'name.min' => 'Category name must be at least 3 characters',
            'name.max' => 'Category name must not exceed 100 characters',
            'name.unique' => 'A category with this name already exists',
            'description.required' => 'Category description is required',
            'description.min' => 'Category description must be at least 10 characters',
            'description.max' => 'Category description must not exceed 500 characters',
        ]);

        // Case-insensitive duplicate check
        $existing = ForumCategory::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A category with this name already exists',
            ], 422);
        }

        $category = DB::transaction(function () use ($validated) {
            $category = ForumCategory::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'type' => $validated['type'],
                'icon' => $validated['type'] === 'academic-qa' ? 'academic' : 'discussion',
            ]);

            if (!empty($validated['hashtags'])) {
                $hashtagIds = [];
                foreach ($validated['hashtags'] as $tagName) {
                    $tag = ForumHashtag::firstOrCreate(['name' => strtolower(trim($tagName))]);
                    $hashtagIds[] = $tag->id;
                }
                $category->hashtags()->sync($hashtagIds);
            }

            return $category;
        });

        $category->load('hashtags');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'type' => $category->type,
                'icon' => $category->icon,
                'hashtags' => $category->hashtags->pluck('name')->toArray(),
                'postCount' => 0,
            ],
        ], 201);
    }

    /**
     * Update a category (admin only)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = ForumCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|min:3|max:100|unique:forum_categories,name,' . $id,
            'description' => 'sometimes|string|min:10|max:500',
            'type' => 'sometimes|in:academic-qa,general-discussion',
            'hashtags' => 'nullable|array',
            'hashtags.*' => 'string|max:100',
        ], [
            'name.min' => 'Category name must be at least 3 characters',
            'name.max' => 'Category name must not exceed 100 characters',
            'name.unique' => 'A category with this name already exists',
            'description.min' => 'Category description must be at least 10 characters',
            'description.max' => 'Category description must not exceed 500 characters',
        ]);

        // Case-insensitive duplicate check
        if (isset($validated['name'])) {
            $existing = ForumCategory::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
                ->where('id', '!=', $id)
                ->first();
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'A category with this name already exists',
                ], 422);
            }
        }

        DB::transaction(function () use ($category, $validated) {
            $category->update(array_filter([
                'name' => $validated['name'] ?? null,
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'] ?? null,
                'icon' => isset($validated['type']) ? ($validated['type'] === 'academic-qa' ? 'academic' : 'discussion') : null,
            ], fn($v) => $v !== null));

            if (isset($validated['hashtags'])) {
                $hashtagIds = [];
                foreach ($validated['hashtags'] as $tagName) {
                    $tag = ForumHashtag::firstOrCreate(['name' => strtolower(trim($tagName))]);
                    $hashtagIds[] = $tag->id;
                }
                $category->hashtags()->sync($hashtagIds);
            }
        });

        $category->load('hashtags');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'type' => $category->type,
                'icon' => $category->icon,
                'hashtags' => $category->hashtags->pluck('name')->toArray(),
                'postCount' => $category->posts()->where('status', 'active')->count(),
            ],
        ]);
    }

    /**
     * Delete a category (admin only)
     */
    public function destroy(int $id): JsonResponse
    {
        $category = ForumCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}
