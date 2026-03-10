<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Forum\ForumHashtag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForumHashtagController extends Controller
{
    /**
     * List all hashtags with post counts
     */
    public function index(): JsonResponse
    {
        $hashtags = ForumHashtag::withCount(['posts' => function ($q) {
            $q->where('status', 'active');
        }])
            ->orderByDesc('posts_count')
            ->get()
            ->map(function ($tag) {
                return [
                    'id' => (string) $tag->id,
                    'name' => $tag->name,
                    'postCount' => $tag->posts_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $hashtags,
        ]);
    }

    /**
     * Get popular/trending hashtags
     */
    public function trending(): JsonResponse
    {
        $hashtags = DB::table('forum_post_hashtag')
            ->join('forum_hashtags', 'forum_hashtags.id', '=', 'forum_post_hashtag.hashtag_id')
            ->join('forum_posts', 'forum_posts.id', '=', 'forum_post_hashtag.post_id')
            ->where('forum_posts.created_at', '>=', now()->subWeek())
            ->where('forum_posts.status', 'active')
            ->select('forum_hashtags.id', 'forum_hashtags.name', DB::raw('count(*) as post_count'))
            ->groupBy('forum_hashtags.id', 'forum_hashtags.name')
            ->orderByDesc('post_count')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $hashtags,
        ]);
    }

    /**
     * Search hashtags (for autocomplete)
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        $hashtags = ForumHashtag::where('name', 'like', '%' . $request->q . '%')
            ->withCount(['posts' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderByDesc('posts_count')
            ->limit(10)
            ->get()
            ->map(function ($tag) {
                return [
                    'id' => (string) $tag->id,
                    'name' => $tag->name,
                    'postCount' => $tag->posts_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $hashtags,
        ]);
    }
}
