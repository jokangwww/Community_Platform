<?php

namespace App\Http\Middleware;

use App\Models\BuddyParticipant;
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

        // Check if user has a buddy participant record
        $participant = BuddyParticipant::where('user_id', Auth::id())->first();
        
        if (!$participant) {
            return response()->json(['error' => 'You are not registered as a buddy participant'], 403);
        }

        // Check if participant is active or matched
        $allowedStatuses = ['active', 'matched'];
        if (!in_array($participant->status, $allowedStatuses)) {
            return response()->json(['error' => 'Your buddy participant account is not active. Current status: ' . $participant->status], 403);
        }

        // Attach participant to request attributes for later use
        // Use attributes->set() instead of merge() becausem$request->get() (Symfony) checks attributes first,
        // and merge() is incompatible with get() for JSON POST requests.
        $request->attributes->set('participant', $participant);

        return $next($request);
    }
}
