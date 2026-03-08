<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubProfileController extends Controller
{
    // Load and render the requested record details page.
    public function show(Request $request, User $club): View
    {
        abort_unless($club->role === 'club', 404);
        abort_if(($club->club_approval_status ?? 'approved') !== 'approved', 404);

        $pastEvents = Event::query()
            ->where('club_id', $club->id)
            ->where('approval_status', 'approved')
            ->where('status', 'ended')
            ->orderByDesc('end_date')
            ->orderByDesc('start_date')
            ->get();

        $viewer = $request->user();
        $view = $viewer && $viewer->role === 'club'
            ? 'club.club-profile'
            : 'user.club-profile';

        return view($view, [
            'club' => $club,
            'pastEvents' => $pastEvents,
        ]);
    }
}
