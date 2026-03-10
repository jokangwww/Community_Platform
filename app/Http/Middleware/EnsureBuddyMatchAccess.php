<?php

namespace App\Http\Middleware;

use App\Models\BuddyMatch;
use App\Models\BuddyParticipant;
use App\Models\BuddySemesterSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBuddyMatchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get matchId from route parameter
        $matchId = $request->route('matchId');
        
        if (!$matchId) {
            return response()->json(['error' => 'Match ID is required'], 400);
        }

        // Check if match exists
        $match = BuddyMatch::where('id', $matchId)->first();

        if (!$match) {
            return response()->json([
                'error' => 'Match not found',
                'debug' => [
                    'matchId' => $matchId,
                ]
            ], 404);
        }

        // Check ALL participant IDs across semesters for this user
        // This allows access to old-semester matches even when the user
        // has a new participant for the current semester
        $allParticipantIds = BuddyParticipant::where('user_id', Auth::id())->pluck('id');

        $participantInMatch = DB::table('buddy_match_participants')
            ->where('match_id', $matchId)
            ->whereIn('participant_id', $allParticipantIds)
            ->first();

        if (!$participantInMatch) {
            return response()->json([
                'error' => 'You do not have access to this match',
                'debug' => [
                    'matchId' => $matchId,
                    'yourParticipantIds' => $allParticipantIds->toArray(),
                ]
            ], 403);
        }

        // Override participant with the one actually linked to this match
        // (may be from a different semester than what EnsureBuddyParticipant resolved)
        $matchParticipant = BuddyParticipant::find($participantInMatch->participant_id);
        $request->attributes->set('participant', $matchParticipant);

        // Re-evaluate readonly: if the match belongs to an archived semester, it's read-only
        $activeSemester = BuddySemesterSetting::getActiveSemester();
        if ($match->semester_id && $activeSemester && $match->semester_id !== $activeSemester->id) {
            $request->attributes->set('readonly', true);
        }

        // Block write operations (POST/PUT/DELETE) on read-only (archived) matches
        if ($request->attributes->get('readonly') && !$request->isMethod('get')) {
            return response()->json([
                'success' => false,
                'message' => 'This semester is archived and read-only. No modifications are allowed.'
            ], 403);
        }

        $request->attributes->set('match', $match);
        $request->attributes->set('isMentor', $participantInMatch->role === 'mentor');
        $request->attributes->set('isMentee', $participantInMatch->role === 'mentee');

        return $next($request);
    }
}
