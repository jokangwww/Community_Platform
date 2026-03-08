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
    // Shared search + lifecycle filters used by all posting list tabs (all/mine/favorites).
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

    // Normalize list filters so invalid lifecycle values do not break the page logic.
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

    // Resolve the authenticated club user once for ownership checks and personalized data.
    private function requireClub(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    // Collect the current user's favorite posting IDs for favorite-state UI rendering.
    private function favoriteIds(User $user): array
    {
        return $user->favoritePostings()
            ->pluck('postings.id')
            ->all();
    }

    // Public/club posting browse tab with search and current/outdated lifecycle filter.
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

    // Club's own postings tab (only postings created by the authenticated club).
    public function mine(Request $request)
    {
        $user = $this->requireClub();
        $userId = (int) $user->getKey();
        $filters = $this->indexFilters($request);

        $query = Posting::with(['club', 'event', 'images'])
            ->withCount('registrations')
            ->where('club_id', $userId)
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

    // Favorite postings tab for club users (same filters reused across tabs).
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

    // Posting create form only allows approved, non-ended events owned by the club.
    public function create()
    {
        $user = $this->requireClub();
        $userId = (int) $user->getKey();

        $events = Event::where('club_id', $userId)
            ->where('status', '!=', 'ended')
            ->where('approval_status', 'approved')
            ->orderBy('name')
            ->get();

        return view('club.event-posting-create', compact('events'));
    }

    // Create a posting for one of the club's approved events and store poster images.
    public function store(Request $request)
    {
        $user = $this->requireClub();
        $userId = (int) $user->getKey();

        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'description' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'in:open,closed,none'],
            'outdated_at' => ['nullable', 'date'],
            'posters' => ['nullable', 'array'],
            'posters.*' => ['image', 'max:2048'],
        ]);

        // Validate ownership by resolving the selected event under this club account.
        $event = Event::where('id', $validated['event_id'])
            ->where('club_id', $userId)
            ->where('approval_status', 'approved')
            ->firstOrFail();

        $posting = Posting::create([
            'club_id' => $userId,
            'event_id' => $event->id,
            'description' => $validated['description'],
            'status' => $validated['status'],
            'outdated_at' => !empty($validated['outdated_at']) ? Carbon::parse($validated['outdated_at']) : null,
            'poster_path' => null,
        ]);

        // Save poster images as separate rows to support multi-image postings.
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

    // Posting edit form with ownership guard and current event choices.
    public function edit(Posting $posting)
    {
        $user = $this->requireClub();
        $userId = (int) $user->getKey();
        $postingClubId = (int) ($posting->getAttribute('club_id') ?? 0);

        if ($postingClubId !== $userId) {
            abort(403);
        }

        $events = Event::where('club_id', $userId)
            ->where('status', '!=', 'ended')
            ->where('approval_status', 'approved')
            ->orderBy('name')
            ->get();

        return view('club.event-posting-edit', compact('posting', 'events'));
    }

    // Posting detail page for clubs (registrants, posters, event info, live stream viewer count).
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

    // Update posting fields/images and trigger notifications when registration status changes to open.
    public function update(Request $request, Posting $posting)
    {
        $user = $this->requireClub();
        $userId = (int) $user->getKey();
        $postingClubId = (int) ($posting->getAttribute('club_id') ?? 0);

        if ($postingClubId !== $userId) {
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
            ->where('club_id', $userId)
            ->where('approval_status', 'approved')
            ->firstOrFail();

        // Detect open transition so favorite followers are only notified when registration opens.
        $wasOpen = ($posting->status ?? 'open') === 'open';

        $posting->event_id = $event->id;
        $posting->description = $validated['description'];
        $posting->status = $validated['status'];
        $posting->outdated_at = !empty($validated['outdated_at']) ? Carbon::parse($validated['outdated_at']) : null;
        $posting->save();

        if (! $wasOpen && $posting->status === 'open') {
            $this->notifyStudentsWhenFavoriteRegistrationOpens($posting);
        }

        // Replacing posters resets the image list and stores the new upload set in order.
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

    // Delete a club-owned posting.
    public function destroy(Posting $posting)
    {
        $user = $this->requireClub();
        $userId = (int) $user->getKey();
        $postingClubId = (int) ($posting->getAttribute('club_id') ?? 0);

        if ($postingClubId !== $userId) {
            abort(403);
        }

        $posting->delete();

        return redirect()
            ->route('club.event-posting.mine')
            ->with('status', 'Posting deleted.');
    }

    // Toggle favorite state for the current user on a posting (used by the shared posting UI).
    public function toggleFavorite(Posting $posting)
    {
        $user = $this->requireClub();
        $postingId = (int) $posting->getKey();

        $user->favoritePostings()->toggle($postingId);

        return redirect()
            ->back();
    }

    // Send notifications to students who favorited the posting when registration is opened.
    private function notifyStudentsWhenFavoriteRegistrationOpens(Posting $posting): void
    {
        $posting->loadMissing('event');

        $students = $posting->favoritedBy()
            ->where('users.role', 'student')
            ->get();

        if ($students->isEmpty()) {
            return;
        }

        Notification::send($students, new FavoritePostingRegistrationOpenedNotification($posting));
    }
}
