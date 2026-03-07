<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Forum\ForumComment;
use App\Models\Forum\ForumCommentLike;
use App\Models\Forum\ForumMention;
use App\Models\Forum\ForumPost;
use App\Models\Forum\ForumReaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForumCommentController extends Controller
{
    /**
     * Get comments for a post (with nested replies)
     */
    public function index(int $postId): JsonResponse
    {
        $userId = Auth::id();
        $post = ForumPost::active()->findOrFail($postId);

        $comments = ForumComment::where('post_id', $postId)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user', 'replies.likes'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($comment) use ($userId) {
                return $this->formatComment($comment, $userId);
            });

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    /**
     * Create a comment
     */
    public function store(Request $request, int $postId): JsonResponse
    {
        $post = ForumPost::active()->findOrFail($postId);

        $validated = $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:forum_comments,id',
        ]);

        $user = Auth::user();

        // Check if user is muted
        if ($user->muted_until && $user->muted_until->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is muted until ' . $user->muted_until->format('d M Y') . '. You cannot post or comment during this period.',
            ], 403);
        }

        $comment = DB::transaction(function () use ($validated, $user, $post) {
            $comment = ForumComment::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'content' => $validated['content'],
            ]);

            $post->increment('comment_count');

            // Handle mentions
            preg_match_all('/@(\w+)/', $validated['content'], $mentionMatches);
            if (!empty($mentionMatches[1])) {
                foreach ($mentionMatches[1] as $nickname) {
                    $mentionedUser = User::where('nickname', $nickname)
                        ->orWhere('name', $nickname)
                        ->first();
                    if ($mentionedUser) {
                        ForumMention::create([
                            'user_id' => $mentionedUser->id,
                            'mentionable_id' => $comment->id,
                            'mentionable_type' => ForumComment::class,
                        ]);
                    }
                }
            }

            return $comment;
        });

        $comment->load('user');

        return response()->json([
            'success' => true,
            'data' => $this->formatComment($comment, $user->id),
        ], 201);
    }

    /**
     * Toggle like on a comment
     */
    public function toggleLike(int $commentId): JsonResponse
    {
        $userId = Auth::id();
        $comment = ForumComment::findOrFail($commentId);

        $existing = ForumCommentLike::where('user_id', $userId)
            ->where('comment_id', $commentId)
            ->first();

        if ($existing) {
            $existing->delete();
            $comment->decrement('likes_count');
            $isLiked = false;
        } else {
            ForumCommentLike::create([
                'user_id' => $userId,
                'comment_id' => $commentId,
            ]);
            $comment->increment('likes_count');
            $isLiked = true;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'isLiked' => $isLiked,
                'likesCount' => $comment->fresh()->likes_count,
            ],
        ]);
    }

    /**
     * Format comment for JSON output
     */
    private function formatComment(ForumComment $comment, ?int $userId): array
    {
        $isVerifiedMentor = false;
        if ($comment->user) {
            $isVerifiedMentor = DB::table('buddy_participants')
                ->where('user_id', $comment->user_id)
                ->where('role', 'mentor')
                ->where('status', 'active')
                ->exists();
        }

        $replies = $comment->replies ? $comment->replies->map(function ($reply) use ($userId) {
            $replyMentor = DB::table('buddy_participants')
                ->where('user_id', $reply->user_id)
                ->where('role', 'mentor')
                ->where('status', 'active')
                ->exists();

            return [
                'id' => (string) $reply->id,
                'authorId' => (string) $reply->user_id,
                'author' => $reply->user->nickname ?? $reply->user->name ?? 'Anonymous',
                'authorAvatar' => strtoupper(substr($reply->user->nickname ?? $reply->user->name ?? 'A', 0, 2)),
                'content' => $reply->content,
                'timeAgo' => $reply->created_at->diffForHumans(),
                'likes' => $reply->likes_count,
                'isLiked' => $userId ? $reply->isLikedBy($userId) : false,
                'isVerifiedMentor' => $replyMentor,
            ];
        })->toArray() : [];

        return [
            'id' => (string) $comment->id,
            'authorId' => (string) $comment->user_id,
            'author' => $comment->user->nickname ?? $comment->user->name ?? 'Anonymous',
            'authorAvatar' => strtoupper(substr($comment->user->nickname ?? $comment->user->name ?? 'A', 0, 2)),
            'content' => $comment->content,
            'timeAgo' => $comment->created_at->diffForHumans(),
            'likes' => $comment->likes_count,
            'isLiked' => $userId ? $comment->isLikedBy($userId) : false,
            'isVerifiedMentor' => $isVerifiedMentor,
            'replies' => $replies,
        ];
    }

    /**
     * Update a comment (owner only)
     */
    public function update(Request $request, int $commentId): JsonResponse
    {
        $user = Auth::user();
        $comment = ForumComment::findOrFail($commentId);

        if ((int) $comment->user_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'You can only edit your own comments.'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update($validated);

        return response()->json([
            'success' => true,
            'data' => $this->formatComment($comment->fresh()->load(['user', 'replies.user', 'replies.likes']), $user->id),
        ]);
    }

    /**
     * Delete a comment (owner only)
     */
    public function destroy(int $commentId): JsonResponse
    {
        $user = Auth::user();
        $comment = ForumComment::findOrFail($commentId);

        if ((int) $comment->user_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'You can only delete your own comments.'], 403);
        }

        $comment->delete();

        return response()->json(['success' => true, 'message' => 'Comment deleted successfully.']);
    }
}
