<?php

namespace App\Http\Middleware;

use App\Models\BuddyParticipant;
use App\Models\BuddySemesterSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBuddyParticipant
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Resolve which semester to scope to:
        // - If ?semester_id is provided in the request, use that (for browsing archived semesters)
        // - Otherwise fall back to the current active semester
        $semesterId = $request->query('semester_id');
        $activeSemester = BuddySemesterSetting::getActiveSemester();

        if ($semesterId) {
            $semesterId = (int) $semesterId;
        } else {
            $semesterId = $activeSemester?->id;
        }

        // Look up participant scoped to the resolved semester
        $query = BuddyParticipant::where('user_id', Auth::id());
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        $participant = $query->first();

        // Fallback: if no participant for the resolved semester, try finding any
        // participant for this user (supports accessing old match/classroom data)
        $usingFallback = false;
        if (!$participant) {
            $participant = BuddyParticipant::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->first();
            $usingFallback = true;
        }

        if (!$participant) {
            return response()->json(['error' => 'You are not registered as a buddy participant'], 403);
        }

        // Determine read-only mode
        $readonly = $participant->continuation_choice === 'declined';

        // Read-only when viewing an archived (non-active) semester
        if (!$readonly && $activeSemester) {
            if ($usingFallback) {
                $readonly = true;
            } elseif ($participant->semester_id && $participant->semester_id !== $activeSemester->id) {
                $readonly = true;
            }
        }

        // Active write access requires active or matched status
        $allowedStatuses = ['active', 'matched'];
        if (!$readonly && !in_array($participant->status, $allowedStatuses)) {
            return response()->json([
                'error' => 'Your buddy participant account is not active. Current status: ' . $participant->status,
            ], 403);
        }

        // Attach participant and read-only flag to request attributes for downstream controllers
        $request->attributes->set('participant', $participant);
        $request->attributes->set('readonly', $readonly);
        $request->attributes->set('semester_id', $semesterId ?: $participant->semester_id);

        return $next($request);
    }
}
