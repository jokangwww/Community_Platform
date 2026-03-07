<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Forum\ForumPost;
use App\Models\Forum\ForumPostAttachment;
use App\Models\Forum\ForumPostLike;
use App\Models\Forum\ForumHashtag;
use App\Models\Forum\ForumCategory;
use App\Models\Forum\ForumMention;
use App\Models\User;
use App\Models\UserModerationAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ForumPostController extends Controller
{
    /**
     * List posts with filtering, searching, sorting
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $query = ForumPost::active()
            ->with(['user', 'category', 'hashtags', 'attachments']);

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by category type
        if ($request->filled('type')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('type', $request->type);
            });
        }

        // Search by keyword (supports #hashtag syntax)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            if (str_starts_with($searchTerm, '#')) {
                // Hashtag search
                $hashtagName = strtolower(ltrim($searchTerm, '#'));
                $query->whereHas('hashtags', function ($q) use ($hashtagName) {
                    $q->where('name', 'like', "%{$hashtagName}%");
                });
            } else {
                $query->search($searchTerm);
            }
        }

        // Filter by hashtag
        if ($request->filled('hashtag')) {
            $query->whereHas('hashtags', function ($q) use ($request) {
                $q->where('name', $request->hashtag);
            });
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'popular':
                $query->orderByDesc('likes_count');
                break;
            case 'most_viewed':
                $query->orderByDesc('views');
                break;
            case 'unanswered':
                $query->whereHas('category', function ($q) {
                    $q->where('type', 'academic-qa');
                })->where('answer_count', 0);
                $query->orderByDesc('created_at');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        $posts = $query->paginate($request->get('per_page', 20));

        $postsData = $posts->getCollection()->map(function ($post) use ($userId) {
            return $this->formatPost($post, $userId);
        });

        return response()->json([
            'success' => true,
            'data' => $postsData,
            'meta' => [
                'currentPage' => $posts->currentPage(),
                'lastPage' => $posts->lastPage(),
                'perPage' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Get a single post with all details
     */
    public function show(int $id): JsonResponse
    {
        $userId = Auth::id();

        $post = ForumPost::active()
            ->with(['user', 'category', 'hashtags', 'attachments'])
            ->findOrFail($id);

        // Increment view count
        $post->increment('views');

        return response()->json([
            'success' => true,
            'data' => $this->formatPost($post, $userId),
        ]);
    }

    /**
     * Create a new post
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'category_id' => 'required|exists:forum_categories,id',
            'hashtags' => 'nullable|array',
            'hashtags.*' => 'string|max:100',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120', // 5MB max per file
        ]);

        $user = Auth::user();

        // Check if user is muted
        if ($user->muted_until && $user->muted_until->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is muted until ' . $user->muted_until->format('d M Y') . '. You cannot post or comment during this period.',
            ], 403);
        }

        $post = DB::transaction(function () use ($validated, $user, $request) {
            $post = ForumPost::create([
                'user_id' => $user->id,
                'category_id' => $validated['category_id'],
                'title' => $validated['title'],
                'content' => $validated['content'],
            ]);

            // Handle hashtags
            if (!empty($validated['hashtags'])) {
                $hashtagIds = [];
                foreach ($validated['hashtags'] as $tagName) {
                    $tag = ForumHashtag::firstOrCreate(['name' => strtolower(trim($tagName))]);
                    $hashtagIds[] = $tag->id;
                }
                $post->hashtags()->sync($hashtagIds);
            }

            // Auto-extract hashtags from content
            preg_match_all('/#(\w+)/', $validated['content'], $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $tagName) {
                    $tag = ForumHashtag::firstOrCreate(['name' => strtolower($tagName)]);
                    $post->hashtags()->syncWithoutDetaching([$tag->id]);
                }
            }

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
                            'mentionable_id' => $post->id,
                            'mentionable_type' => ForumPost::class,
                        ]);
                    }
                }
            }

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('forum/attachments', 'public');
                    ForumPostAttachment::create([
                        'post_id' => $post->id,
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'type' => $file->getMimeType(),
                        'size' => $this->formatFileSize($file->getSize()),
                    ]);
                }
            }

            return $post;
        });

        $post->load(['user', 'category', 'hashtags', 'attachments']);

        return response()->json([
            'success' => true,
            'data' => $this->formatPost($post, $user->id),
        ], 201);
    }

    /**
     * Toggle like on a post
     */
    public function toggleLike(int $id): JsonResponse
    {
        $userId = Auth::id();
        $post = ForumPost::active()->findOrFail($id);

        $existing = ForumPostLike::where('user_id', $userId)
            ->where('post_id', $post->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $post->decrement('likes_count');
            $isLiked = false;
        } else {
            ForumPostLike::create([
                'user_id' => $userId,
                'post_id' => $post->id,
            ]);
            $post->increment('likes_count');
            $isLiked = true;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'isLiked' => $isLiked,
                'likesCount' => $post->fresh()->likes_count,
            ],
        ]);
    }

    /**
     * Search posts by hashtag
     */
    public function searchByHashtag(Request $request): JsonResponse
    {
        $request->validate([
            'hashtag' => 'required|string',
        ]);

        $userId = Auth::id();

        $posts = ForumPost::active()
            ->with(['user', 'category', 'hashtags'])
            ->whereHas('hashtags', function ($q) use ($request) {
                $q->where('name', strtolower($request->hashtag));
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($post) use ($userId) {
                return $this->formatPost($post, $userId);
            });

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * Get user dashboard data — matches UserDashboard interface
     */
    public function userDashboard(): JsonResponse
    {
        $userId = Auth::id();

        $postsCreated = ForumPost::where('user_id', $userId)->count();
        $commentsPosted = DB::table('forum_comments')->where('user_id', $userId)->count();
        $answersGiven = DB::table('forum_answers')->where('user_id', $userId)->count();
        $upvotesReceived = (int) DB::table('forum_answers')
            ->where('user_id', $userId)
            ->sum('upvotes_count');
        $likesReceived = (int) ForumPost::where('user_id', $userId)->sum('likes_count');
        $acceptedAnswers = DB::table('forum_answers')
            ->where('user_id', $userId)
            ->where('is_accepted', true)
            ->count();
        $totalViews = (int) ForumPost::where('user_id', $userId)->sum('views');

        // Build activity feed
        $activities = collect();

        // Accepted answers
        $acceptedActivity = DB::table('forum_answers')
            ->join('forum_posts', 'forum_posts.id', '=', 'forum_answers.post_id')
            ->where('forum_answers.user_id', $userId)
            ->where('forum_answers.is_accepted', true)
            ->select(
                'forum_answers.id',
                'forum_posts.id as post_id',
                'forum_posts.title as post_title',
                'forum_answers.updated_at as timestamp'
            )
            ->orderByDesc('forum_answers.updated_at')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'id' => 'accepted-' . $row->id,
                'type' => 'accepted',
                'title' => 'Your answer was accepted',
                'description' => "Your answer on '{$row->post_title}' was marked as the best answer",
                'timestamp' => \Carbon\Carbon::parse($row->timestamp)->diffForHumans(),
                'postId' => (string) $row->post_id,
                'category' => null,
                'isUnread' => \Carbon\Carbon::parse($row->timestamp)->isToday(),
            ]);
        $activities = $activities->merge($acceptedActivity);

        // Recent upvotes on user's posts
        $likeActivity = DB::table('forum_post_likes')
            ->join('forum_posts', 'forum_posts.id', '=', 'forum_post_likes.post_id')
            ->where('forum_posts.user_id', $userId)
            ->select(
                'forum_post_likes.id',
                'forum_posts.id as post_id',
                'forum_posts.title as post_title',
                'forum_posts.likes_count',
                'forum_post_likes.created_at as timestamp'
            )
            ->orderByDesc('forum_post_likes.created_at')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'id' => 'upvote-' . $row->id,
                'type' => 'upvote',
                'title' => "Your post received likes",
                'description' => "Your post '{$row->post_title}' has {$row->likes_count} likes",
                'timestamp' => \Carbon\Carbon::parse($row->timestamp)->diffForHumans(),
                'postId' => (string) $row->post_id,
                'category' => null,
                'isUnread' => \Carbon\Carbon::parse($row->timestamp)->isToday(),
            ]);
        $activities = $activities->merge($likeActivity);

        // Mentions
        $mentionActivity = DB::table('forum_mentions')
            ->where('forum_mentions.user_id', $userId)
            ->where('forum_mentions.mentionable_type', ForumPost::class)
            ->join('forum_posts', 'forum_posts.id', '=', 'forum_mentions.mentionable_id')
            ->join('users', 'users.id', '=', 'forum_posts.user_id')
            ->select(
                'forum_mentions.id',
                'forum_posts.id as post_id',
                'forum_posts.title as post_title',
                DB::raw("COALESCE(users.nickname, users.name) as author_name"),
                'forum_mentions.created_at as timestamp'
            )
            ->orderByDesc('forum_mentions.created_at')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'id' => 'mention-' . $row->id,
                'type' => 'mention',
                'title' => 'You were mentioned',
                'description' => "{$row->author_name} mentioned you in '{$row->post_title}'",
                'timestamp' => \Carbon\Carbon::parse($row->timestamp)->diffForHumans(),
                'postId' => (string) $row->post_id,
                'category' => null,
                'isUnread' => \Carbon\Carbon::parse($row->timestamp)->isToday(),
            ]);
        $activities = $activities->merge($mentionActivity);

        // Comments on user's posts
        $commentActivity = DB::table('forum_comments')
            ->join('forum_posts', 'forum_posts.id', '=', 'forum_comments.post_id')
            ->join('users', 'users.id', '=', 'forum_comments.user_id')
            ->where('forum_posts.user_id', $userId)
            ->where('forum_comments.user_id', '!=', $userId)
            ->select(
                'forum_comments.id',
                'forum_posts.id as post_id',
                'forum_posts.title as post_title',
                DB::raw("COALESCE(users.nickname, users.name) as commenter_name"),
                'forum_comments.created_at as timestamp'
            )
            ->orderByDesc('forum_comments.created_at')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'id' => 'comment-' . $row->id,
                'type' => 'comment',
                'title' => 'New reply on your post',
                'description' => "{$row->commenter_name} replied to your post '{$row->post_title}'",
                'timestamp' => \Carbon\Carbon::parse($row->timestamp)->diffForHumans(),
                'postId' => (string) $row->post_id,
                'category' => null,
                'isUnread' => \Carbon\Carbon::parse($row->timestamp)->isToday(),
            ]);
        $activities = $activities->merge($commentActivity);

        // Moderation actions (warn/mute/delete)
        $moderationActivity = UserModerationAction::where('user_id', $userId)
            ->whereIn('action', ['warn', 'mute', 'delete'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($action) {
                // Fetch a short preview of the reported content so the user knows which post/comment was actioned
                $contentSnippet = null;
                if ($action->content_id) {
                    try {
                        if ($action->content_type === 'post') {
                            $content = \App\Models\Forum\ForumPost::find($action->content_id);
                            $contentSnippet = $content ? \Illuminate\Support\Str::limit($content->content, 80) : null;
                        } elseif ($action->content_type === 'answer') {
                            $content = \App\Models\Forum\ForumAnswer::find($action->content_id);
                            $contentSnippet = $content ? \Illuminate\Support\Str::limit($content->content, 80) : null;
                        } elseif ($action->content_type === 'comment') {
                            $content = \App\Models\Forum\ForumComment::find($action->content_id);
                            $contentSnippet = $content ? \Illuminate\Support\Str::limit($content->content, 80) : null;
                        }
                    } catch (\Throwable) {
                        // Content may have been deleted; snippet stays null
                    }
                }

                $actionLabels = [
                    'warn' => ['Warning Received', 'You received a warning from a moderator'],
                    'mute' => ['Account Muted', 'Your account was muted for ' . ($action->mute_duration_days ?? 0) . ' day(s)'],
                    'delete' => ['Content Removed', 'Your content was removed by a moderator'],
                ];
                $label = $actionLabels[$action->action] ?? ['Moderation Action', 'A moderation action was taken on your account'];

                // Build description: base message + which content + optional admin note
                $contentTypeLabel = $action->content_type ?? 'content';
                $description = $label[1];
                if ($contentSnippet) {
                    $description .= " — Your {$contentTypeLabel}: \"{$contentSnippet}\"";
                }
                if ($action->note) {
                    $description .= " Admin note: \"{$action->note}\"";
                }

                // Resolve the parent post ID from the content so the user can click through
                $resolvedPostId = '';
                if ($action->content_id) {
                    try {
                        if ($action->content_type === 'post') {
                            // Check the post still exists (even if soft-deleted with status='deleted')
                            $resolvedPostId = (string) $action->content_id;
                        } elseif ($action->content_type === 'answer') {
                            $answer = \App\Models\Forum\ForumAnswer::find($action->content_id);
                            if ($answer) {
                                $resolvedPostId = (string) $answer->post_id;
                            }
                        } elseif ($action->content_type === 'comment') {
                            $comment = \App\Models\Forum\ForumComment::find($action->content_id);
                            if ($comment) {
                                $resolvedPostId = (string) $comment->post_id;
                            }
                        }
                    } catch (\Throwable) {
                        // Content may have been hard-deleted; postId stays empty
                    }
                }

                return [
                    'id' => 'moderation-' . $action->id,
                    'type' => 'moderation',
                    'title' => $label[0],
                    'description' => $description,
                    'timestamp' => $action->created_at->diffForHumans(),
                    'postId' => $resolvedPostId,
                    'category' => 'Moderation',
                    'isUnread' => $action->created_at->isToday(),
                ];
            });
        $activities = $activities->merge($moderationActivity);

        // Sort all activities by most recent and take top 10
        $sortedActivities = $activities->sortByDesc(function ($a) {
            return $a['timestamp'];
        })->take(10)->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'postsCreated' => $postsCreated,
                    'commentsPosted' => $commentsPosted,
                    'answersGiven' => $answersGiven,
                    'upvotesReceived' => $upvotesReceived + $likesReceived,
                    'acceptedAnswers' => $acceptedAnswers,
                    'totalViews' => $totalViews,
                ],
                'activities' => $sortedActivities,
            ],
        ]);
    }

    /**
     * Format a post for JSON output
     */
    private function formatPost(ForumPost $post, ?int $userId): array
    {
        $isVerifiedMentor = false;
        // Check if user is a verified mentor from buddy programme
        if ($post->user) {
            $isVerifiedMentor = DB::table('buddy_participants')
                ->where('user_id', $post->user_id)
                ->where('role', 'mentor')
                ->where('status', 'active')
                ->exists();
        }

        return [
            'id' => (string) $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'author' => [
                'id' => (string) $post->user_id,
                'nickname' => $post->user->nickname ?? $post->user->name ?? 'Anonymous',
                'isVerifiedMentor' => $isVerifiedMentor,
            ],
            'category' => [
                'id' => (string) $post->category_id,
                'name' => $post->category->name,
                'type' => $post->category->type,
            ],
            'hashtags' => $post->hashtags->pluck('name')->toArray(),
            'attachments' => $post->attachments ? $post->attachments->map(function ($att) {
                return [
                    'id' => (string) $att->id,
                    'name' => $att->name,
                    'type' => $att->type,
                    'size' => $att->size,
                    'url' => Storage::url($att->path),
                ];
            })->toArray() : [],
            'createdAt' => $post->created_at->diffForHumans(),
            'views' => $post->views,
            'likes' => $post->likes_count,
            'commentCount' => $post->comment_count,
            'answerCount' => $post->category?->type === 'academic-qa' ? $post->answer_count : null,
            'hasAcceptedAnswer' => $post->has_accepted_answer,
            'isLiked' => $userId ? $post->isLikedBy($userId) : false,
        ];
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        return number_format($bytes / 1024, 1) . ' KB';
    }

    /**
     * Update a post (owner only)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $post = ForumPost::findOrFail($id);

        if ((int) $post->user_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'You can only edit your own posts.'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:200',
            'content' => 'sometimes|required|string',
        ]);

        $post->update($validated);
        $post->load(['user', 'category', 'hashtags', 'attachments']);

        return response()->json([
            'success' => true,
            'data' => $this->formatPost($post, $user->id),
        ]);
    }

    /**
     * Delete a post (owner only)
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        $post = ForumPost::findOrFail($id);

        if ((int) $post->user_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'You can only delete your own posts.'], 403);
        }

        $post->update(['status' => 'deleted']);

        return response()->json(['success' => true, 'message' => 'Post deleted successfully.']);
    }
}
