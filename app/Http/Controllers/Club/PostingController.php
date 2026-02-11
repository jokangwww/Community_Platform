<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Posting;
use App\Models\User;
use Illuminate\Http\Request;

class PostingController extends Controller
{
    private function requireClub(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    private function favoriteIds(User $user): array
    {
        return $user->favoritePostings()
            ->pluck('postings.id')
            ->all();
    }

    public function index()
    {
        $user = $this->requireClub();

        $postings = Posting::with(['event', 'images'])
            ->withCount('registrations')
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended')
                    ->where('approval_status', 'approved');
            })
            ->latest()
            ->get();

        return view('club.event-posting', [
            'postings' => $postings,
            'activeTab' => 'all',
            'favoriteIds' => $this->favoriteIds($user),
        ]);
    }

    public function mine()
    {
        $user = $this->requireClub();

        $postings = Posting::with(['event', 'images'])
            ->withCount('registrations')
            ->where('club_id', $user->id)
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended')
                    ->where('approval_status', 'approved');
            })
            ->latest()
            ->get();

        return view('club.event-posting', [
            'postings' => $postings,
            'activeTab' => 'mine',
            'favoriteIds' => $this->favoriteIds($user),
        ]);
    }

    public function favorites()
    {
        $user = $this->requireClub();

        $postings = $user->favoritePostings()
            ->with(['event', 'images'])
            ->withCount('registrations')
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended')
                    ->where('approval_status', 'approved');
            })
            ->latest('postings.created_at')
            ->get();

        return view('club.event-posting', [
            'postings' => $postings,
            'activeTab' => 'favorites',
            'favoriteIds' => $this->favoriteIds($user),
        ]);
    }

    public function create()
    {
        $user = $this->requireClub();

        $events = Event::where('club_id', $user->id)
            ->where('status', '!=', 'ended')
            ->where('approval_status', 'approved')
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
            'status' => ['required', 'in:open,closed'],
            'posters' => ['nullable', 'array'],
            'posters.*' => ['image', 'max:2048'],
        ]);

        $event = Event::where('id', $validated['event_id'])
            ->where('club_id', $user->id)
            ->where('approval_status', 'approved')
            ->firstOrFail();

        $posting = Posting::create([
            'club_id' => $user->id,
            'event_id' => $event->id,
            'description' => $validated['description'],
            'status' => $validated['status'],
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
            ->where('status', '!=', 'ended')
            ->where('approval_status', 'approved')
            ->orderBy('name')
            ->get();

        return view('club.event-posting-edit', compact('posting', 'events'));
    }

    public function show(Posting $posting)
    {
        $user = $this->requireClub();

        $posting->load(['event', 'images', 'registrations.student']);
        if (($posting->event?->status ?? 'in_progress') === 'ended') {
            abort(404);
        }

        return view('club.event-posting-show', [
            'posting' => $posting,
            'favoriteIds' => $this->favoriteIds($user),
        ]);
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
            'status' => ['required', 'in:open,closed'],
            'posters' => ['nullable', 'array'],
            'posters.*' => ['image', 'max:2048'],
        ]);

        $event = Event::where('id', $validated['event_id'])
            ->where('club_id', $user->id)
            ->where('approval_status', 'approved')
            ->firstOrFail();

        $posting->event_id = $event->id;
        $posting->description = $validated['description'];
        $posting->status = $validated['status'];
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

    public function toggleFavorite(Posting $posting)
    {
        $user = $this->requireClub();

        $user->favoritePostings()->toggle($posting->id);

        return redirect()
            ->back();
    }
}
