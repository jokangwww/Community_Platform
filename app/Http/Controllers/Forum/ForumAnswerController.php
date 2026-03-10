<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Forum\ForumAnswer;
use App\Models\Forum\ForumMention;
use App\Models\Forum\ForumPost;
use App\Models\Forum\ForumReaction;
use App\Models\Forum\ForumVote;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForumAnswerController extends Controller
{
    /**
     * Get answers for a post
     */
    public function index(int $postId): JsonResponse
    {
        $userId = Auth::id();
        $post = ForumPost::active()->findOrFail($postId);

        $answers = ForumAnswer::where('post_id', $postId)
            ->with(['user', 'reactions', 'mentions'])
            ->orderByDesc('is_accepted')
            ->orderByRaw('(CAST(upvotes_count AS SIGNED) - CAST(downvotes_count AS SIGNED)) DESC')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($answer) use ($userId) {
                return $this->formatAnswer($answer, $userId);
            });

        return response()->json([
            'success' => true,
            'data' => $answers,
        ]);
    }

    /**
     * Create an answer for a post
     */
    public function store(Request $request, int $postId): JsonResponse
    {
        $post = ForumPost::active()->findOrFail($postId);

        // Only allow answers on academic Q&A posts
        if ($post->category->type !== 'academic-qa') {
            return response()->json([
                'success' => false,
                'message' => 'Answers are only allowed on Academic Q&A posts.',
            ], 422);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'mentions' => 'nullable|array',
            'mentions.*' => 'string',
        ]);

        $user = Auth::user();

        // Check if user is muted
        if ($user->muted_until && $user->muted_until->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is muted until ' . $user->muted_until->format('d M Y') . '. You cannot post or comment during this period.',
            ], 403);
        }

        $answer = DB::transaction(function () use ($validated, $user, $post) {
            $answer = ForumAnswer::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'content' => $validated['content'],
            ]);

            $post->increment('answer_count');

            // Handle mentions
            if (!empty($validated['mentions'])) {
                foreach ($validated['mentions'] as $nickname) {
                    $mentionedUser = User::where('nickname', $nickname)
                        ->orWhere('name', $nickname)
                        ->first();
                    if ($mentionedUser) {
                        ForumMention::create([
                            'user_id' => $mentionedUser->id,
                            'mentionable_id' => $answer->id,
                            'mentionable_type' => ForumAnswer::class,
                        ]);
                    }
                }
            }

            return $answer;
        });

        $answer->load(['user', 'reactions', 'mentions']);

        return response()->json([
            'success' => true,
            'data' => $this->formatAnswer($answer, $user->id),
        ], 201);
    }

    /**
     * Vote on an answer (upvote/downvote)
     */
    public function vote(Request $request, int $answerId): JsonResponse
    {
        $validated = $request->validate([
            'vote_type' => 'required|in:up,down',
        ]);

        $userId = Auth::id();
        $answer = ForumAnswer::findOrFail($answerId);

        $existingVote = ForumVote::where('user_id', $userId)
            ->where('answer_id', $answerId)
            ->first();

        DB::transaction(function () use ($existingVote, $validated, $userId, $answer, $answerId) {
            if ($existingVote) {
                if ($existingVote->vote_type === $validated['vote_type']) {
                    // Remove vote (toggle off)
                    if ($existingVote->vote_type === 'up') {
                        $answer->decrement('upvotes_count');
                    } else {
                        $answer->decrement('downvotes_count');
                    }
                    $existingVote->delete();
                } else {
                    // Change vote direction
                    if ($existingVote->vote_type === 'up') {
                        $answer->decrement('upvotes_count');
                        $answer->increment('downvotes_count');
                    } else {
                        $answer->decrement('downvotes_count');
                        $answer->increment('upvotes_count');
                    }
                    $existingVote->update(['vote_type' => $validated['vote_type']]);
                }
            } else {
                // New vote
                ForumVote::create([
                    'user_id' => $userId,
                    'answer_id' => $answerId,
                    'vote_type' => $validated['vote_type'],
                ]);
                if ($validated['vote_type'] === 'up') {
                    $answer->increment('upvotes_count');
                } else {
                    $answer->increment('downvotes_count');
                }
            }
        });

        $answer->refresh();
        $currentVote = ForumVote::where('user_id', $userId)
            ->where('answer_id', $answerId)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'upvotes' => $answer->upvotes_count,
                'downvotes' => $answer->downvotes_count,
                'userVote' => $currentVote?->vote_type,
            ],
        ]);
    }

    /**
     * Accept an answer (only by question owner)
     */
    public function acceptAnswer(int $answerId): JsonResponse
    {
        $userId = Auth::id();
        $answer = ForumAnswer::with('post')->findOrFail($answerId);

        if ($answer->post->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Only the question author can accept an answer.',
            ], 403);
        }

        DB::transaction(function () use ($answer) {
            // Unaccept previous accepted answer
            ForumAnswer::where('post_id', $answer->post_id)
                ->where('is_accepted', true)
                ->update(['is_accepted' => false]);

            // Accept this answer
            $answer->update(['is_accepted' => true]);
            $answer->post->update(['has_accepted_answer' => true]);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'answerId' => (string) $answer->id,
                'isAccepted' => true,
            ],
        ]);
    }

    /**
     * React to an answer with an emoji
     */
    public function react(Request $request, int $answerId): JsonResponse
    {
        $validated = $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $userId = Auth::id();
        $answer = ForumAnswer::findOrFail($answerId);

        $existing = ForumReaction::where('user_id', $userId)
            ->where('reactable_id', $answerId)
            ->where('reactable_type', ForumAnswer::class)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            ForumReaction::create([
                'user_id' => $userId,
                'reactable_id' => $answerId,
                'reactable_type' => ForumAnswer::class,
                'emoji' => $validated['emoji'],
            ]);
        }

        // Get updated reactions
        $reactions = ForumReaction::where('reactable_id', $answerId)
            ->where('reactable_type', ForumAnswer::class)
            ->select('emoji', DB::raw('count(*) as count'))
            ->groupBy('emoji')
            ->get()
            ->map(function ($r) use ($userId, $answerId) {
                return [
                    'emoji' => $r->emoji,
                    'count' => $r->count,
                    'userReacted' => ForumReaction::where('user_id', $userId)
                        ->where('reactable_id', $answerId)
                        ->where('reactable_type', ForumAnswer::class)
                        ->where('emoji', $r->emoji)
                        ->exists(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $reactions,
        ]);
    }

    /**
     * Format answer for JSON output
     */
    private function formatAnswer(ForumAnswer $answer, ?int $userId): array
    {
        $isVerifiedMentor = false;
        if ($answer->user) {
            $isVerifiedMentor = DB::table('buddy_participants')
                ->where('user_id', $answer->user_id)
                ->where('role', 'mentor')
                ->where('status', 'active')
                ->exists();
        }

        $reactions = ForumReaction::where('reactable_id', $answer->id)
            ->where('reactable_type', ForumAnswer::class)
            ->select('emoji', DB::raw('count(*) as count'))
            ->groupBy('emoji')
            ->get()
            ->map(function ($r) use ($userId, $answer) {
                return [
                    'emoji' => $r->emoji,
                    'count' => $r->count,
                    'userReacted' => $userId ? ForumReaction::where('user_id', $userId)
                        ->where('reactable_id', $answer->id)
                        ->where('reactable_type', ForumAnswer::class)
                        ->where('emoji', $r->emoji)
                        ->exists() : false,
                ];
            });

        $mentionedNames = $answer->mentions
            ? $answer->mentions->map(function ($m) {
                $user = User::find($m->user_id);
                return $user ? ($user->nickname ?? $user->name) : null;
            })->filter()->values()->toArray()
            : [];

        return [
            'id' => (string) $answer->id,
            'postId' => (string) $answer->post_id,
            'content' => $answer->content,
            'author' => [
                'id' => (string) $answer->user_id,
                'nickname' => $answer->user?->nickname ?? $answer->user?->name ?? 'Anonymous',
                'isVerifiedMentor' => $isVerifiedMentor,
            ],
            'upvotes' => $answer->upvotes_count,
            'downvotes' => $answer->downvotes_count,
            'isAccepted' => $answer->is_accepted,
            'userVote' => $userId ? $answer->getUserVote($userId) : null,
            'reactions' => $reactions->toArray(),
            'createdAt' => $answer->created_at->diffForHumans(),
            'mentions' => $mentionedNames,
        ];
    }
}
