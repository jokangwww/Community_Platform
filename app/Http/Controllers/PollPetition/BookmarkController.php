<?php

namespace App\Http\Controllers\PollPetition;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Petition;
use App\Models\PetitionSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    /**
     * Toggle bookmark for a poll or petition.
     * POST /api/poll-petition/bookmarks/toggle
     * Body: { type: 'poll'|'petition', id: number }
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:poll,petition',
            'id'   => 'required|integer',
        ]);

        $type = $request->input('type');
        $id   = (int) $request->input('id');

        // Verify the item exists (including archived/expired/disabled)
        $modelClass = Bookmark::resolveModelClass($type);
        if (!$modelClass || !$modelClass::where('id', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $isNowBookmarked = Bookmark::toggle(Auth::id(), $type, $id);

        return response()->json([
            'success'      => true,
            'isBookmarked' => $isNowBookmarked,
            'message'      => $isNowBookmarked ? 'Bookmarked successfully' : 'Bookmark removed',
        ]);
    }

    /**
     * Get all bookmarked polls and petitions for the current user.
     * GET /api/poll-petition/bookmarks
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();

        $bookmarks = Bookmark::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        $pollIds     = $bookmarks->where('bookmarkable_type', 'poll')->pluck('bookmarkable_id');
        $petitionIds = $bookmarks->where('bookmarkable_type', 'petition')->pluck('bookmarkable_id');

        // Fetch polls (include ALL statuses so archived/expired work)
        $polls = Poll::with(['options.votes'])
            ->whereIn('id', $pollIds)
            ->get()
            ->map(function ($poll) use ($userId) {
                $isExpired = $poll->is_expired;
                $totalVotes = $poll->votes()->count();
                $myVote = PollVote::where('poll_id', $poll->id)
                    ->where('user_id', $userId)
                    ->first();
                $myOptionText = $myVote ? $myVote->option?->text : null;

                return [
                    'id'          => (string) $poll->id,
                    'title'       => $poll->title,
                    'category'    => ucwords(str_replace('-', ' ', $poll->category)),
                    'myVote'      => $myOptionText,
                    'totalVotes'  => $totalVotes,
                    'expiresAt'   => $isExpired
                        ? 'Expired ' . $poll->expires_at->diffForHumans()
                        : $poll->expires_at->diffForHumans(),
                    'status'      => $poll->status === 'disabled' ? 'archived' : ($isExpired ? 'expired' : $poll->status),
                    'isBookmarked' => true,
                    'createdByMe' => $poll->user_id === $userId,
                    'results'     => $poll->options->map(fn($o) => [
                        'option'     => $o->text,
                        'votes'      => $o->votes->count(),
                        'percentage' => $totalVotes > 0 ? round($o->votes->count() / $totalVotes * 100) : 0,
                    ])->toArray(),
                ];
            });

        // Fetch petitions (include ALL statuses so archived work)
        $petitions = Petition::with(['supports'])
            ->whereIn('id', $petitionIds)
            ->get()
            ->map(function ($petition) use ($userId) {
                $supporters = $petition->supports->count();
                return [
                    'id'          => (string) $petition->id,
                    'title'       => $petition->title,
                    'description' => $petition->description,
                    'category'    => 'General',
                    'supporters'  => $supporters,
                    'goal'        => $petition->supporter_goal ?? 500,
                    'expiresAt'   => $petition->status === 'active'
                        ? $petition->created_at->addMonths(3)->diffForHumans()
                        : ($petition->status === 'closed' ? 'Closed' : 'Active'),
                    'status'      => $petition->status === 'disabled' ? 'archived' : $petition->status,
                    'iSupported'  => $petition->hasUserSupported($userId),
                    'isBookmarked' => true,
                    'createdByMe' => $petition->user_id === $userId,
                ];
            });

        return response()->json([
            'success'   => true,
            'polls'     => $polls,
            'petitions' => $petitions,
        ]);
    }
}
