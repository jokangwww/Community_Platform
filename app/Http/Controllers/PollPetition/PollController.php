<?php

namespace App\Http\Controllers\PollPetition;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\PollRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    /**
     * List active polls (with optional search/filter).
     * Targeted polls are only shown to users matching the criteria.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $user   = Auth::user();

        // Auto-expire overdue polls
        Poll::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $query = Poll::with(['user', 'options.votes'])
            ->where('status', '!=', 'disabled');

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('status', 'active')->where('expires_at', '>', now());
            } elseif ($request->status === 'expired') {
                $query->where(function ($q) {
                    $q->where('status', 'expired')
                      ->orWhere('expires_at', '<=', now());
                });
            }
        }

        // Filter by category (special value 'targeted' shows only targeted polls)
        if ($request->filled('category') && $request->category !== 'all') {
            if ($request->category === 'targeted') {
                $query->where(function ($q) {
                    $q->whereNotNull('target_faculty')
                      ->orWhereNotNull('target_year')
                      ->orWhereNotNull('target_course');
                });
            } else {
                $query->where('category', $request->category);
            }
        }

        // Search
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'latest');
        if ($sortBy === 'popularity') {
            $query->withCount('votes')->orderByDesc('votes_count');
        } else {
            $query->orderByDesc('created_at');
        }

        $polls = $query->get();

        // Filter out targeted polls that don't match the current user
        $polls = $polls->filter(function ($poll) use ($user) {
            return $this->isEligibleForPoll($poll, $user);
        });

        $result = $polls->values()->map(function ($poll) use ($userId) {
            return $this->formatPoll($poll, $userId);
        });

        return response()->json($result);
    }

    /**
     * Show single poll with full details.
     */
    public function show(int $id): JsonResponse
    {
        $userId = Auth::id();

        $poll = Poll::with(['user', 'options.votes', 'ratings'])
            ->findOrFail($id);

        return response()->json($this->formatPoll($poll, $userId));
    }

    /**
     * Create a new poll (7-day cooldown enforced).
     */
    public function store(Request $request): JsonResponse
    {
        $userId = Auth::id();

        // Check cooldown: one poll every 7 days
        $lastPoll = Poll::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        if ($lastPoll && $lastPoll->created_at->diffInDays(now()) < 7) {
            $nextDate = $lastPoll->created_at->addDays(7)->toDateString();
            return response()->json([
                'message' => 'You can only create one poll every 7 days.',
                'next_available_date' => $nextDate,
            ], 422);
        }

        $validated = $request->validate([
            'title'           => 'required|string|max:100',
            'description'     => 'required|string|max:500',
            'options'         => 'required|array|min:2|max:5',
            'options.*'       => 'required|string|max:100',
            'expiry_date'     => 'required|date|after:today',
            'category'        => 'required|string|max:50',
            'target_faculty'  => 'nullable|string|max:100',
            'target_year'     => 'nullable|string|max:50',
            'target_course'   => 'nullable|string|max:100',
        ]);

        $poll = DB::transaction(function () use ($validated, $userId) {
            $poll = Poll::create([
                'user_id'        => $userId,
                'title'          => $validated['title'],
                'description'    => $validated['description'],
                'category'       => $validated['category'],
                'expires_at'     => $validated['expiry_date'],
                'target_faculty' => $validated['target_faculty'] ?? null,
                'target_year'    => $validated['target_year'] ?? null,
                'target_course'  => $validated['target_course'] ?? null,
            ]);

            foreach ($validated['options'] as $index => $text) {
                PollOption::create([
                    'poll_id'  => $poll->id,
                    'text'     => $text,
                    'position' => $index,
                ]);
            }

            return $poll;
        });

        $poll->load(['user', 'options.votes']);

        return response()->json($this->formatPoll($poll, $userId), 201);
    }

    /**
     * Vote on a poll.
     */
    public function vote(Request $request, int $id): JsonResponse
    {
        $userId = Auth::id();
        $poll = Poll::findOrFail($id);

        // Cannot vote on expired / disabled polls
        if ($poll->is_expired || $poll->status !== 'active') {
            return response()->json(['message' => 'This poll is no longer active.'], 422);
        }

        // Check already voted
        if ($poll->hasUserVoted($userId)) {
            return response()->json(['message' => 'You have already voted on this poll.'], 422);
        }

        $validated = $request->validate([
            'option_id' => 'required|exists:poll_options,id',
        ]);

        // Ensure the option belongs to this poll
        $option = PollOption::where('id', $validated['option_id'])
            ->where('poll_id', $poll->id)
            ->firstOrFail();

        PollVote::create([
            'poll_id'        => $poll->id,
            'poll_option_id' => $option->id,
            'user_id'        => $userId,
        ]);

        $poll->load(['user', 'options.votes', 'ratings']);

        return response()->json($this->formatPoll($poll, $userId));
    }

    /**
     * Rate a poll's usefulness after voting.
     */
    public function rate(Request $request, int $id): JsonResponse
    {
        $userId = Auth::id();
        $poll = Poll::findOrFail($id);

        if (!$poll->hasUserVoted($userId)) {
            return response()->json(['message' => 'You must vote before rating.'], 422);
        }

        if ($poll->hasUserRated($userId)) {
            return response()->json(['message' => 'You have already rated this poll.'], 422);
        }

        $validated = $request->validate([
            'is_useful' => 'required|boolean',
        ]);

        PollRating::create([
            'poll_id'   => $poll->id,
            'user_id'   => $userId,
            'is_useful'  => $validated['is_useful'],
        ]);

        $poll->load(['user', 'options.votes', 'ratings']);

        return response()->json($this->formatPoll($poll, $userId));
    }

    /**
     * Check whether the current user can create a poll.
     */
    public function canCreate(): JsonResponse
    {
        $userId = Auth::id();

        $lastPoll = Poll::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        $canCreate = true;
        $nextDate = null;

        if ($lastPoll && $lastPoll->created_at->diffInDays(now()) < 7) {
            $canCreate = false;
            $nextDate = $lastPoll->created_at->addDays(7)->toDateString();
        }

        return response()->json([
            'can_create'          => $canCreate,
            'next_available_date' => $nextDate,
        ]);
    }

    /**
     * Get archived (expired) polls.
     */
    public function archived(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $user   = Auth::user();

        // Auto-expire overdue polls
        Poll::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $query = Poll::with(['user', 'options.votes', 'ratings'])
            ->where(function ($q) {
                $q->where('status', 'expired')
                  ->orWhere('expires_at', '<=', now());
            })
            ->where('status', '!=', 'disabled');

        // Filter by category (special value 'targeted' shows only targeted polls)
        if ($request->filled('category') && $request->category !== 'all') {
            if ($request->category === 'targeted') {
                $query->where(function ($q) {
                    $q->whereNotNull('target_faculty')
                      ->orWhereNotNull('target_year')
                      ->orWhereNotNull('target_course');
                });
            } else {
                $query->where('category', $request->category);
            }
        }

        // Search
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'date');
        if ($sortBy === 'popularity') {
            $query->withCount('votes')->orderByDesc('votes_count');
        } elseif ($sortBy === 'usefulness') {
            // Sort by usefulness score (requires subquery)
            $query->withCount([
                'ratings as useful_count' => function ($q) {
                    $q->where('is_useful', true);
                },
                'ratings as total_ratings',
            ])->orderByRaw('CASE WHEN total_ratings > 0 THEN useful_count * 100.0 / total_ratings ELSE 0 END DESC');
        } else {
            $query->orderByDesc('created_at');
        }

        $polls = $query->get();

        // Filter out targeted polls that don't match the current user
        $polls = $polls->filter(function ($poll) use ($user) {
            return $this->isEligibleForPoll($poll, $user);
        });

        $result = $polls->values()->map(function ($poll) use ($userId) {
            return $this->formatPoll($poll, $userId);
        });

        return response()->json($result);
    }

    /**
     * Check whether a user is eligible to see a targeted poll.
     * Non-targeted polls (no criteria set) are visible to everyone.
     * Admins always see all polls.
     */
    private function isEligibleForPoll(Poll $poll, $user): bool
    {
        $isTargeted = $poll->target_faculty || $poll->target_year || $poll->target_course;

        // Non-targeted polls are visible to everyone
        if (!$isTargeted) {
            return true;
        }

        // Admins see all polls
        if ($user && $user->role === 'admin') {
            return true;
        }

        // Poll creator always sees their own poll
        if ($user && $poll->user_id === $user->id) {
            return true;
        }

        // Check each criterion — all set criteria must match
        if ($poll->target_faculty && $user) {
            if (strtolower($user->faculty ?? '') !== strtolower($poll->target_faculty)) {
                return false;
            }
        }

        if ($poll->target_year && $user) {
            // target_year stored as e.g. "1st year", user study_year as integer
            $yearMap = ['1st year' => 1, '2nd year' => 2, '3rd year' => 3, '4th year' => 4];
            $targetYear = $yearMap[strtolower($poll->target_year)] ?? null;
            if ($targetYear && (int) ($user->study_year ?? 0) !== $targetYear) {
                return false;
            }
        }

        if ($poll->target_course && $user) {
            if (strtolower($user->programme ?? '') !== strtolower($poll->target_course)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format a poll model for JSON output.
     */
    private function formatPoll(Poll $poll, int $userId): array
    {
        $totalVotes = $poll->votes->count();

        return [
            'id'          => (string) $poll->id,
            'title'       => $poll->title,
            'description' => $poll->description,
            'category'    => $poll->category,
            'status'      => $poll->status,
            'author'      => $poll->user->nickname ?? $poll->user->name,
            'createdAt'   => $poll->created_at->format('M j, Y'),
            'expiryDate'  => $poll->expires_at->toDateString(),
            'totalVotes'  => $totalVotes,
            'hasVoted'    => $poll->hasUserVoted($userId),
            'isExpired'   => $poll->is_expired,
            'hasRated'    => $poll->hasUserRated($userId),
            'usefulnessScore' => $poll->usefulness_score,
            'isOfficial'  => $poll->is_official,
            'options'     => $poll->options->map(fn($o) => [
                'id'    => (string) $o->id,
                'text'  => $o->text,
                'votes' => $o->votes->count(),
            ]),
            'targetCriteria' => ($poll->target_faculty || $poll->target_year || $poll->target_course) ? [
                'faculty'     => $poll->target_faculty,
                'yearOfStudy' => $poll->target_year,
                'course'      => $poll->target_course,
            ] : null,
        ];
    }
}
