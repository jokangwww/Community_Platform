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
use Illuminate\Support\Facades\DB;

class PollPetitionDashboardController extends Controller
{
    /**
     * Combined user dashboard data for polls + petitions.
     * Includes: my polls, my petitions, top campus voices, top campus concerns.
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();

        // ── My polls (created + voted) ──
        $createdPolls = Poll::with(['options.votes'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => $this->formatDashboardPoll($p, $userId, true));

        $votedPollIds = PollVote::where('user_id', $userId)->pluck('poll_id');
        $votedPolls = Poll::with(['options.votes'])
            ->whereIn('id', $votedPollIds)
            ->where('user_id', '!=', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => $this->formatDashboardPoll($p, $userId, false));

        // ── My petitions (created + supported) ──
        $createdPetitions = Petition::with(['supports'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => $this->formatDashboardPetition($p, $userId, true));

        $supportedIds = PetitionSupport::where('user_id', $userId)->pluck('petition_id');
        $supportedPetitions = Petition::with(['supports'])
            ->whereIn('id', $supportedIds)
            ->where('user_id', '!=', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => $this->formatDashboardPetition($p, $userId, false));

        // ── Top 3 Campus Voices (highest total votes/interactions) ──
        $topVoices = $this->getTopCampusVoices();

        // ── Top 3 Campus Concerns (highest week-over-week increase) ──
        $topConcerns = $this->getTopCampusConcerns();

        return response()->json([
            'polls' => [
                'created' => $createdPolls,
                'voted'   => $votedPolls,
            ],
            'petitions' => [
                'created'   => $createdPetitions,
                'supported' => $supportedPetitions,
            ],
            'topCampusVoices'   => $topVoices,
            'topCampusConcerns' => $topConcerns,
        ]);
    }

    /**
     * Top 3 Campus Voices: polls/petitions with highest total interactions.
     */
    private function getTopCampusVoices(): array
    {
        // Top polls by votes
        $topPolls = Poll::with('user')
            ->withCount('votes')
            ->where('status', '!=', 'disabled')
            ->orderByDesc('votes_count')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id'                => (string) $p->id,
                'title'             => $p->title,
                'type'              => 'poll',
                'totalVotes'        => $p->votes_count,
                'totalInteractions' => $p->votes_count,
                'author'            => $p->user->nickname ?? $p->user->name,
                'authorAvatar'      => strtoupper(substr($p->user->nickname ?? $p->user->name, 0, 2)),
            ]);

        // Top petitions by supporters
        $topPetitions = Petition::with('user')
            ->withCount('supports')
            ->where('status', '!=', 'disabled')
            ->orderByDesc('supports_count')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id'                => (string) $p->id,
                'title'             => $p->title,
                'type'              => 'petition',
                'totalVotes'        => $p->supports_count,
                'totalInteractions' => $p->supports_count,
                'author'            => $p->user->nickname ?? $p->user->name,
                'authorAvatar'      => strtoupper(substr($p->user->nickname ?? $p->user->name, 0, 2)),
            ]);

        return $topPolls->merge($topPetitions)
            ->sortByDesc('totalInteractions')
            ->take(3)
            ->values()
            ->toArray();
    }

    /**
     * Top 3 Campus Concerns: highest week-over-week participation increase.
     */
    private function getTopCampusConcerns(): array
    {
        $oneWeekAgo = now()->subWeek();
        $twoWeeksAgo = now()->subWeeks(2);

        // Poll concerns
        $pollConcerns = Poll::with('user')
            ->where('status', '!=', 'disabled')
            ->get()
            ->map(function ($poll) use ($oneWeekAgo, $twoWeeksAgo) {
                $thisWeek = $poll->votes()->where('created_at', '>=', $oneWeekAgo)->count();
                $lastWeek = $poll->votes()
                    ->where('created_at', '>=', $twoWeeksAgo)
                    ->where('created_at', '<', $oneWeekAgo)
                    ->count();
                $increase = $thisWeek - $lastWeek;

                return [
                    'id'                    => (string) $poll->id,
                    'title'                 => $poll->title,
                    'type'                  => 'poll',
                    'currentParticipants'   => $poll->votes()->count(),
                    'weekOverWeekIncrease'  => max(0, $increase),
                    'category'              => $poll->category,
                ];
            });

        // Petition concerns
        $petitionConcerns = Petition::where('status', '!=', 'disabled')
            ->get()
            ->map(function ($petition) use ($oneWeekAgo, $twoWeeksAgo) {
                $thisWeek = $petition->supports()->where('created_at', '>=', $oneWeekAgo)->count();
                $lastWeek = $petition->supports()
                    ->where('created_at', '>=', $twoWeeksAgo)
                    ->where('created_at', '<', $oneWeekAgo)
                    ->count();
                $increase = $thisWeek - $lastWeek;

                return [
                    'id'                    => (string) $petition->id,
                    'title'                 => $petition->title,
                    'type'                  => 'petition',
                    'currentParticipants'   => $petition->supports()->count(),
                    'weekOverWeekIncrease'  => max(0, $increase),
                    'category'              => 'General',
                ];
            });

        return $pollConcerns->merge($petitionConcerns)
            ->sortByDesc('weekOverWeekIncrease')
            ->take(3)
            ->values()
            ->toArray();
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
