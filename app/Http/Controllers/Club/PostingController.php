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

        $postings = Posting::with(['event', 'images'])
            ->latest()
            ->get();

        return view('club.event-posting', [
            'postings' => $postings,
            'activeTab' => 'all',
        ]);
    }

    public function mine()
    {
        $user = $this->requireClub();

        $postings = Posting::with(['event', 'images'])
            ->where('club_id', $user->id)
            ->latest()
            ->get();

        return view('club.event-posting', [
            'postings' => $postings,
            'activeTab' => 'mine',
        ]);
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
            'posters' => ['nullable', 'array'],
            'posters.*' => ['image', 'max:2048'],
        ]);

        $event = Event::where('id', $validated['event_id'])
            ->where('club_id', $user->id)
            ->firstOrFail();

        $posting = Posting::create([
            'club_id' => $user->id,
            'event_id' => $event->id,
            'description' => $validated['description'],
            'poster_path' => null,
        ]);

        if ($request->hasFile('posters')) {
            foreach ($request->file('posters') as $index => $file) {
                $path = $file->store('posters', 'public');
                $posting->images()->create([
                    'image_path' => $path,
                    'position' => $index,
                ]);
            }
        }

        return redirect()
            ->route('club.event-posting.mine')
            ->with('status', 'Posting created.');
    }

    public function edit(Posting $posting)
    {
        $user = $this->requireClub();

        if ($posting->club_id !== $user->id) {
            abort(403);
        }

        $events = Event::where('club_id', $user->id)
            ->orderBy('name')
            ->get();

        return view('club.event-posting-edit', compact('posting', 'events'));
    }

    public function update(Request $request, Posting $posting)
    {
        $user = $this->requireClub();

        if ($posting->club_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'description' => ['required', 'string', 'max:2000'],
            'posters' => ['nullable', 'array'],
            'posters.*' => ['image', 'max:2048'],
        ]);

        $event = Event::where('id', $validated['event_id'])
            ->where('club_id', $user->id)
            ->firstOrFail();

        $posting->event_id = $event->id;
        $posting->description = $validated['description'];
        $posting->save();

        if ($request->hasFile('posters')) {
            $posting->images()->delete();
            foreach ($request->file('posters') as $index => $file) {
                $path = $file->store('posters', 'public');
                $posting->images()->create([
                    'image_path' => $path,
                    'position' => $index,
                ]);
            }
        }

        return redirect()
            ->route('club.event-posting.mine')
            ->with('status', 'Posting updated.');
    }

    public function destroy(Posting $posting)
    {
        $user = $this->requireClub();

        if ($posting->club_id !== $user->id) {
            abort(403);
        }

        $posting->delete();

        return redirect()
            ->route('club.event-posting.mine')
            ->with('status', 'Posting deleted.');
    }
}
