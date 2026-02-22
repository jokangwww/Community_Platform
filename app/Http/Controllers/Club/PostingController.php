<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Posting;
use App\Models\User;
use App\Notifications\FavoritePostingRegistrationOpenedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PostingController extends Controller
{
    private function applySearchAndLifecycleFilters($query, Request $request): void
    {
        $keyword = trim((string) $request->query('q', ''));
        $lifecycle = (string) $request->query('lifecycle', 'all');

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('description', 'like', '%' . $keyword . '%')
                    ->orWhereHas('event', function ($eventQuery) use ($keyword) {
                        $eventQuery->where('name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('club', function ($clubQuery) use ($keyword) {
                        $clubQuery->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('display_name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($lifecycle === 'current') {
            $query->where(function ($builder) {
                $builder->whereNull('outdated_at')
                    ->orWhere('outdated_at', '>', now());
            });
        } elseif ($lifecycle === 'outdated') {
            $query->whereNotNull('outdated_at')
                ->where('outdated_at', '<=', now());
        }
    }

    private function indexFilters(Request $request): array
    {
        $lifecycle = (string) $request->query('lifecycle', 'all');
        if (! in_array($lifecycle, ['all', 'current', 'outdated'], true)) {
            $lifecycle = 'all';
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'lifecycle' => $lifecycle,
        ];
    }

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

    public function index(Request $request)
    {
        $user = $this->requireClub();
        $filters = $this->indexFilters($request);

        $query = Posting::with(['club', 'event', 'images'])
            ->withCount('registrations')
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended')
                    ->where('approval_status', 'approved');
            })
            ->latest();

        $this->applySearchAndLifecycleFilters($query, $request);
        $postings = $query->get();

        return view('club.event-posting', [
            'postings' => $postings,
            'activeTab' => 'all',
            'favoriteIds' => $this->favoriteIds($user),
            'filters' => $filters,
        ]);
    }

    public function mine(Request $request)
    {
        $user = $this->requireClub();
        $filters = $this->indexFilters($request);

        $query = Posting::with(['club', 'event', 'images'])
            ->withCount('registrations')
            ->where('club_id', $user->id)
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended')
                    ->where('approval_status', 'approved');
            })
            ->latest();

        $this->applySearchAndLifecycleFilters($query, $request);
        $postings = $query->get();

        return view('club.event-posting', [
            'postings' => $postings,
            'activeTab' => 'mine',
            'favoriteIds' => $this->favoriteIds($user),
            'filters' => $filters,
        ]);
    }

    public function favorites(Request $request)
    {
        $user = $this->requireClub();
        $filters = $this->indexFilters($request);

        $query = $user->favoritePostings()
            ->with(['club', 'event', 'images'])
            ->withCount('registrations')
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended')
                    ->where('approval_status', 'approved');
            })
            ->latest('postings.created_at');

        $this->applySearchAndLifecycleFilters($query, $request);
        $postings = $query->get();

        return view('club.event-posting', [
            'postings' => $postings,
            'activeTab' => 'favorites',
            'favoriteIds' => $this->favoriteIds($user),
            'filters' => $filters,
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
            'status' => ['required', 'in:open,closed,none'],
            'outdated_at' => ['nullable', 'date'],
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
            'outdated_at' => !empty($validated['outdated_at']) ? Carbon::parse($validated['outdated_at']) : null,
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
            'streamViewerCount' => $posting->event?->activeStreamViewerCount() ?? 0,
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
            'status' => ['required', 'in:open,closed,none'],
            'outdated_at' => ['nullable', 'date'],
            'posters' => ['nullable', 'array'],
            'posters.*' => ['image', 'max:2048'],
        ]);

        $event = Event::where('id', $validated['event_id'])
            ->where('club_id', $user->id)
            ->where('approval_status', 'approved')
            ->firstOrFail();

        $wasOpen = ($posting->status ?? 'open') === 'open';

        $posting->event_id = $event->id;
        $posting->description = $validated['description'];
        $posting->status = $validated['status'];
        $posting->outdated_at = !empty($validated['outdated_at']) ? Carbon::parse($validated['outdated_at']) : null;
        $posting->save();

        if (! $wasOpen && $posting->status === 'open') {
            $this->notifyStudentsWhenFavoriteRegistrationOpens($posting);
        }

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

    private function notifyStudentsWhenFavoriteRegistrationOpens(Posting $posting): void
    {
        $posting->loadMissing('event');

        $students = $posting->favoritedBy()
            ->where('role', 'student')
            ->get();

        if ($students->isEmpty()) {
            return;
        }

        Notification::send($students, new FavoritePostingRegistrationOpenedNotification($posting));
    }
}
