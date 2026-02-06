<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use App\Models\Posting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PostingController extends Controller
{
    private function requireUser(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function favoriteIds(User $user): array
    {
        return $user->favoritePostings()
            ->pluck('postings.id')
            ->all();
    }

    private function registeredPostingIds(User $user): array
    {
        if ($user->role !== 'student') {
            return [];
        }

        return EventRegistration::where('student_id', $user->id)
            ->pluck('posting_id')
            ->all();
    }

    private function eventRegistrationCounts(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        return EventRegistration::query()
            ->join('postings', 'event_registrations.posting_id', '=', 'postings.id')
            ->whereIn('postings.event_id', $eventIds)
            ->groupBy('postings.event_id')
            ->selectRaw('postings.event_id, COUNT(*) as total')
            ->pluck('total', 'postings.event_id')
            ->all();
    }

    public function index()
    {
        $user = $this->requireUser();

        $postings = Posting::with(['event.ticketSetting', 'images'])
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended');
            })
            ->latest()
            ->get();

        return view('user.event-posting', [
            'postings' => $postings,
            'activeTab' => 'all',
            'favoriteIds' => $this->favoriteIds($user),
            'registeredIds' => $this->registeredPostingIds($user),
            'canRegister' => $user->role === 'student',
            'eventRegistrationCounts' => $this->eventRegistrationCounts($postings->pluck('event_id')->filter()->unique()->all()),
        ]);
    }

    public function favorites()
    {
        $user = $this->requireUser();

        $postings = $user->favoritePostings()
            ->with(['event.ticketSetting', 'images'])
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended');
            })
            ->latest('postings.created_at')
            ->get();

        return view('user.event-posting', [
            'postings' => $postings,
            'activeTab' => 'favorites',
            'favoriteIds' => $this->favoriteIds($user),
            'registeredIds' => $this->registeredPostingIds($user),
            'canRegister' => $user->role === 'student',
            'eventRegistrationCounts' => $this->eventRegistrationCounts($postings->pluck('event_id')->filter()->unique()->all()),
        ]);
    }

    public function show(Posting $posting)
    {
        $user = $this->requireUser();

        $posting->load(['event.ticketSetting', 'images']);
        if (($posting->event?->status ?? 'in_progress') === 'ended') {
            abort(404);
        }

        return view('user.event-posting-show', [
            'posting' => $posting,
            'favoriteIds' => $this->favoriteIds($user),
            'registeredIds' => $this->registeredPostingIds($user),
            'canRegister' => $user->role === 'student',
            'eventRegistrationCounts' => $this->eventRegistrationCounts($posting->event_id ? [$posting->event_id] : []),
        ]);
    }

    public function register(Posting $posting)
    {
        $user = $this->requireUser();

        if ($user->role !== 'student') {
            abort(403);
        }

        if (($posting->status ?? 'open') !== 'open') {
            return redirect()
                ->back()
                ->with('status', 'Registration is closed for this event.');
        }

        $posting->loadMissing('event');
        if (($posting->event?->status ?? 'in_progress') === 'ended') {
            return redirect()
                ->back()
                ->with('status', 'This event has ended.');
        }
        $limit = $posting->event?->participant_limit;
        if (($posting->event?->registration_type ?? 'register') === 'ticket') {
            return redirect()
                ->back()
                ->with('status', 'This event requires a ticket purchase.');
        }
        if ($limit) {
            $currentCount = EventRegistration::whereHas('posting', function ($query) use ($posting) {
                $query->where('event_id', $posting->event_id);
            })->count();
            if ($currentCount >= $limit) {
                return redirect()
                    ->back()
                    ->with('status', 'Registration limit reached for this event.');
            }
        }

        EventRegistration::firstOrCreate([
            'posting_id' => $posting->id,
            'student_id' => $user->id,
        ]);

        return redirect()
            ->back()
            ->with('status', 'Registration submitted.');
    }

    public function toggleFavorite(Posting $posting)
    {
        $user = $this->requireUser();

        $user->favoritePostings()->toggle($posting->id);

        return redirect()
            ->back();
    }
}
