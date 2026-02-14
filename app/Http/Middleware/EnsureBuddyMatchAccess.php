<?php

namespace App\Http\Middleware;

use App\Models\BuddyMatch;
use Illuminate\Support\Facades\DB;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBuddyMatchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get participant from previous middleware
        $participant = $request->get('participant');
        
        if (!$participant) {
            return response()->json(['error' => 'Participant information not found'], 403);
        }

        // Get matchId from route parameter
        $matchId = $request->route('matchId');
        
        if (!$matchId) {
            return response()->json(['error' => 'Match ID is required'], 400);
        }

        // Check if user has access to this match via pivot table
        $match = BuddyMatch::where('id', $matchId)->first();

        if (!$match) {
            return response()->json([
                'error' => 'Match not found',
                'debug' => [
                    'matchId' => $matchId,
                ]
            ], 404);
        }

        // Check if participant is in this match (via pivot table)
        $participantInMatch = DB::table('buddy_match_participants')
            ->where('match_id', $matchId)
            ->where('participant_id', $participant->id)
            ->first();

        if (!$participantInMatch) {
            return response()->json([
                'error' => 'You do not have access to this match',
                'debug' => [
                    'matchId' => $matchId,
                    'yourParticipantId' => $participant->id,
                ]
            ], 403);
        }

        $request->attributes->set('match', $match);
        $request->attributes->set('isMentor', $participantInMatch->role === 'mentor');
        $request->attributes->set('isMentee', $participantInMatch->role === 'mentee');

        return $next($request);
    }
}
