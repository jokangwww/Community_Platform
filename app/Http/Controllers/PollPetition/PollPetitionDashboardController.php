<?php

namespace App\Http\Controllers\PollPetition;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Petition;
use App\Models\PetitionSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PollPetitionDashboardController extends Controller
{
    /**
     * Combined user dashboard data for polls + petitions.
     * Includes: my polls and my petitions.
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();

        // ── My polls (created + voted) ──
        $createdPolls = Poll::with(['options.votes'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Poll $p) => $this->formatDashboardPoll($p, $userId, true));

        $votedPollIds = PollVote::where('user_id', $userId)->pluck('poll_id');
        $votedPolls = Poll::with(['options.votes'])
            ->whereIn('id', $votedPollIds)
            ->where('user_id', '!=', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Poll $p) => $this->formatDashboardPoll($p, $userId, false));

        // ── My petitions (created + supported) ──
        $createdPetitions = Petition::with(['supports'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Petition $p) => $this->formatDashboardPetition($p, $userId, true));

        $supportedIds = PetitionSupport::where('user_id', $userId)->pluck('petition_id');
        $supportedPetitions = Petition::with(['supports'])
            ->whereIn('id', $supportedIds)
            ->where('user_id', '!=', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Petition $p) => $this->formatDashboardPetition($p, $userId, false));

        return response()->json([
            'polls' => [
                'created' => $createdPolls,
                'voted'   => $votedPolls,
            ],
            'petitions' => [
                'created'   => $createdPetitions,
                'supported' => $supportedPetitions,
            ],
        ]);
    }

    /* ── Formatting helpers ───────────────────────── */

    private function formatDashboardPoll(Poll $poll, int $userId, bool $createdByMe): array
    {
        $isExpired = $poll->is_expired;
        $totalVotes = $poll->votes()->count();
        $myVote = null;

        if (!$createdByMe) {
            $vote = PollVote::where('poll_id', $poll->id)->where('user_id', $userId)->first();
            $myVote = $vote ? $vote->option?->text : null;
        }

        return [
            'id'          => (string) $poll->id,
            'title'       => $poll->title,
            'category'    => ucwords(str_replace('-', ' ', $poll->category)),
            'myVote'      => $myVote,
            'totalVotes'  => $totalVotes,
            'expiresAt'   => $isExpired
                ? 'Expired ' . $poll->expires_at->diffForHumans()
                : $poll->expires_at->diffForHumans(),
            'status'      => $isExpired ? 'expired' : $poll->status,
            'isBookmarked'=> Bookmark::isBookmarked($userId, 'poll', $poll->id),
            'createdByMe' => $createdByMe,
            'results'     => $poll->options->map(fn($o) => [
                'option'     => $o->text,
                'votes'      => $o->votes->count(),
                'percentage' => $totalVotes > 0 ? round($o->votes->count() / $totalVotes * 100) : 0,
            ])->toArray(),
        ];
    }

    private function formatDashboardPetition(Petition $petition, int $userId, bool $createdByMe): array
    {
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
            'status'      => $petition->status,
            'iSupported'  => $petition->hasUserSupported($userId),
            'isBookmarked'=> Bookmark::isBookmarked($userId, 'petition', $petition->id),
            'createdByMe' => $createdByMe,
        ];
    }
}
