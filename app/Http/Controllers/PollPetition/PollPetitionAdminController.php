<?php

namespace App\Http\Controllers\PollPetition;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Petition;
use App\Models\PetitionSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PollPetitionAdminController extends Controller
{
    /**
     * Admin poll listing with management data.
     */
    public function polls(Request $request): JsonResponse
    {
        // Auto-expire overdue polls
        Poll::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $query = Poll::with(['user', 'options.votes', 'ratings'])
            ->withCount('votes');

        // Filter by category
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('status', 'active')->where('expires_at', '>', now());
            } elseif ($request->status === 'expired') {
                $query->where(function ($q) {
                    $q->where('status', 'expired')->orWhere('expires_at', '<=', now());
                });
            } else {
                $query->where('status', $request->status);
            }
        }

        $polls = $query->orderByDesc('created_at')->get();

        $result = $polls->map(function ($poll) {
            $totalVotes = $poll->votes->count();
            $usefulCount = $poll->ratings->where('is_useful', true)->count();
            $totalRatings = $poll->ratings->count();
            $usefulnessScore = $totalRatings > 0 ? round($usefulCount / $totalRatings * 100, 1) : null;

            return [
                'id'            => (string) $poll->id,
                'title'         => $poll->title,
                'category'      => $poll->category,
                'creator'       => $poll->user->nickname ?? $poll->user->name,
                'creatorAvatar' => strtoupper(substr($poll->user->nickname ?? $poll->user->name, 0, 2)),
                'totalVotes'    => $totalVotes,
                'participation' => $totalVotes, // could be enhanced with total eligible user count
                'expiresAt'     => $poll->expires_at->toDateString(),
                'createdAt'     => $poll->created_at->toDateString(),
                'status'        => $poll->is_expired ? 'expired' : $poll->status,
                'isOfficial'    => $poll->is_official,
                'hasDisputes'   => $usefulnessScore !== null && $usefulnessScore < 30,
                'disputeCount'  => $poll->ratings->where('is_useful', false)->count(),
                'usefulnessScore' => $usefulnessScore,
            ];
        });

        return response()->json(['polls' => $result]);
    }

    /**
     * Admin petition listing with management data.
     */
    public function petitions(Request $request): JsonResponse
    {
        // Auto-expire overdue petitions
        Petition::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'closed']);

        $query = Petition::with(['user', 'supports']);

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $petitions = $query->orderByDesc('created_at')->get();

        $result = $petitions->map(function ($petition) {
            $supporters = $petition->supports->count();

            // Use actual expires_at if set; otherwise fall back to created_at + 3 months
            $expiresAt = $petition->expires_at
                ? $petition->expires_at->toDateString()
                : $petition->created_at->addMonths(3)->toDateString();

            // Determine effective status (respect expires_at even if DB status lags)
            $effectiveStatus = $petition->status;
            if ($effectiveStatus === 'active' && $petition->expires_at && $petition->expires_at <= now()) {
                $effectiveStatus = 'closed';
            }

            return [
                'id'            => (string) $petition->id,
                'title'         => $petition->title,
                'description'   => $petition->description,
                'category'      => 'General',
                'creator'       => $petition->user->nickname ?? $petition->user->name,
                'creatorAvatar' => strtoupper(substr($petition->user->nickname ?? $petition->user->name, 0, 2)),
                'supporters'    => $supporters,
                'goal'          => $petition->supporter_goal ?? 1000,
                'participation' => $supporters,
                'expiresAt'     => $expiresAt,
                'createdAt'     => $petition->created_at->toDateString(),
                'status'        => $effectiveStatus,
                'isOfficial'    => $petition->is_official,
                'hasDisputes'   => false,
                'disputeCount'  => 0,
            ];
        });

        return response()->json(['petitions' => $result]);
    }

    /**
     * Disable a poll.
     */
    public function disablePoll(int $id): JsonResponse
    {
        $poll = Poll::findOrFail($id);
        $poll->update(['status' => 'disabled']);

        return response()->json(['message' => 'Poll has been disabled.']);
    }

    /**
     * Disable a petition.
     */
    public function disablePetition(int $id): JsonResponse
    {
        $petition = Petition::findOrFail($id);
        $petition->update(['status' => 'disabled']);

        return response()->json(['message' => 'Petition has been disabled.']);
    }

    /**
     * Extend a poll deadline.
     */
    public function extendPollDeadline(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'new_deadline' => 'required|date|after:today',
        ]);

        $poll = Poll::findOrFail($id);
        $poll->update([
            'expires_at' => $validated['new_deadline'],
            'status'     => 'active', // re-activate if it was expired
        ]);

        return response()->json(['message' => 'Poll deadline extended.']);
    }

    /**
     * Extend a petition deadline.
     */
    public function extendPetitionDeadline(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'new_deadline' => 'required|date|after:today',
        ]);

        $petition = Petition::findOrFail($id);
        $petition->update([
            'expires_at' => $validated['new_deadline'],
            'status'     => 'active', // re-activate if it was expired
        ]);

        return response()->json(['message' => 'Petition deadline extended.']);
    }

    /**
     * Mark a poll as official.
     */
    public function publishOfficialPoll(int $id): JsonResponse
    {
        $poll = Poll::findOrFail($id);
        $poll->update(['is_official' => true]);

        return response()->json(['message' => 'Poll published as official.']);
    }

    /**
     * Mark a petition as official.
     */
    public function publishOfficialPetition(int $id): JsonResponse
    {
        $petition = Petition::findOrFail($id);
        $petition->update(['is_official' => true]);

        return response()->json(['message' => 'Petition published as official.']);
    }

    /**
     * Get admin analytics summary.
     */
    public function analytics(): JsonResponse
    {
        // Auto-expire overdue polls
        Poll::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $totalPolls = Poll::count();
        $activePolls = Poll::where('status', 'active')->where('expires_at', '>', now())->count();
        $totalPollVotes = PollVote::count();

        $totalPetitions = Petition::count();
        $activePetitions = Petition::where('status', 'active')->count();
        $totalSupporters = PetitionSupport::count();
        $successfulPetitions = Petition::where('status', 'successful')->count();

        // Low-usefulness polls (< 30%)
        $lowUsefulnessPolls = DB::table('polls')
            ->join('poll_ratings', 'polls.id', '=', 'poll_ratings.poll_id')
            ->select('polls.id')
            ->groupBy('polls.id')
            ->havingRaw('SUM(CASE WHEN poll_ratings.is_useful = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) < 30')
            ->count();

        // Polls with disputes (usefulness < 30%)
        $pollsWithDisputes = $lowUsefulnessPolls;

        // Low-participation petitions (< 30% of goal)
        $lowParticipationPetitions = Petition::whereNotNull('supporter_goal')
            ->where('supporter_goal', '>', 0)
            ->whereRaw('(SELECT COUNT(*) FROM petition_supports WHERE petition_supports.petition_id = petitions.id) * 100.0 / supporter_goal < 30')
            ->count();

        // Average per day calculations
        $firstPoll = Poll::orderBy('created_at', 'asc')->first();
        $firstPetition = Petition::orderBy('created_at', 'asc')->first();

        $pollDays = $firstPoll ? max(1, now()->diffInDays($firstPoll->created_at) + 1) : 1;
        $petitionDays = $firstPetition ? max(1, now()->diffInDays($firstPetition->created_at) + 1) : 1;

        $averagePollPerDay = round($totalPolls / $pollDays, 1);
        $averagePetitionPerDay = round($totalPetitions / $petitionDays, 1);

        return response()->json([
            'polls' => [
                'total'             => $totalPolls,
                'active'            => $activePolls,
                'totalVotes'        => $totalPollVotes,
                'lowParticipation'  => $lowUsefulnessPolls,
                'hasDisputes'       => $pollsWithDisputes,
                'averagePollPerDay' => $averagePollPerDay,
            ],
            'petitions' => [
                'total'                 => $totalPetitions,
                'active'                => $activePetitions,
                'totalSupporters'       => $totalSupporters,
                'successful'            => $successfulPetitions,
                'lowParticipation'      => $lowParticipationPetitions,
                'hasDisputes'           => 0,
                'averagePetitionPerDay' => $averagePetitionPerDay,
            ],
        ]);
    }

    /**
     * Export analytics data (JSON format — front-end can convert to PDF).
     */
    public function exportAnalytics(Request $request): JsonResponse
    {
        $type = $request->get('type', 'all');

        $data = [
            'generatedAt' => now()->toISOString(),
            'period'      => 'All time',
            'type'        => $type,
        ];

        if ($type === 'polls' || $type === 'all') {
            $polls = Poll::with(['options.votes', 'ratings'])->get();
            $data['polls'] = [
                'total'                => $polls->count(),
                'active'               => $polls->where('status', 'active')->count(),
                'totalVotes'           => PollVote::count(),
                'averageVotesPerPoll'  => $polls->count() > 0 ? round(PollVote::count() / $polls->count(), 1) : 0,
            ];
        }

        if ($type === 'petitions' || $type === 'all') {
            $petitions = Petition::withCount('supports')->get();
            $data['petitions'] = [
                'total'            => $petitions->count(),
                'active'           => $petitions->where('status', 'active')->count(),
                'totalSupporters'  => PetitionSupport::count(),
                'averageSupporters'=> $petitions->count() > 0 ? round(PetitionSupport::count() / $petitions->count(), 1) : 0,
            ];
        }

        return response()->json($data);
    }
}
