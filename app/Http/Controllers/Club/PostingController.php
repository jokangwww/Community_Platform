<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Posting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostingController extends Controller
{
    private function requireClub()
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            abort(403);
        }

        return $user;
    }

    public function index()
    {
        $user = $this->requireClub();

        $postings = Posting::with('event')
            ->where('club_id', $user->id)
            ->latest()
            ->get();

        return view('club.event-posting', compact('postings'));
    }

    public function create()
    {
        $user = $this->requireClub();

        $events = Event::where('club_id', $user->id)
            ->orderBy('name')
            ->get();

        return view('club.event-posting-create', compact('events'));
    }

    public function store(Request $request)
    {
        $user = $this->requireClub();

        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'description' => ['required', 'string', 'max:2000'],
            'poster' => ['nullable', 'image', 'max:2048'],
        ]);

        $event = Event::where('id', $validated['event_id'])
            ->where('club_id', $user->id)
            ->firstOrFail();

        $posterPath = null;
        if ($request->hasFile('poster')) {
            $posterPath = $request->file('poster')->store('posters', 'public');
        }

        Posting::create([
            'club_id' => $user->id,
            'event_id' => $event->id,
            'description' => $validated['description'],
            'poster_path' => $posterPath,
        ]);

        return redirect()
            ->route('club.event-posting')
            ->with('status', 'Posting created.');
    }
}
