<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSubEvent;
use App\Models\EventFacultyLimit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EventController extends Controller
{
    private function parseCommitteeIds(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $items = array_filter(array_map('trim', explode(',', $raw)));
        return array_values(array_unique($items));
    }

    private function resolveCommitteeUsers(array $committeeIds): array
    {
        if ($committeeIds === []) {
            return [];
        }

        $users = User::whereIn('student_id', $committeeIds)
            ->where('role', 'student')
            ->get(['id', 'student_id']);

        $found = $users->pluck('student_id')->all();

        $missing = array_values(array_diff($committeeIds, $found));
        if ($missing) {
            throw ValidationException::withMessages([
                'committee_student_ids' => 'Student ID not found: ' . implode(', ', $missing),
            ]);
        }

        return $users->pluck('id')->all();
    }

    private function normalizeSubEvents(array $titles, array $dates): array
    {
        $items = [];
        foreach ($titles as $index => $title) {
            $cleanTitle = trim($title);
            if ($cleanTitle === '') {
                continue;
            }
            $items[] = [
                'title' => $cleanTitle,
                'event_date' => $dates[$index] ?? null,
            ];
        }
        return $items;
    }

    private function storeSubEvents(Event $event, array $subEvents): void
    {
        $event->subEvents()->delete();
        foreach ($subEvents as $subEvent) {
            $event->subEvents()->create([
                'title' => $subEvent['title'],
                'event_date' => $subEvent['event_date'] ?: null,
            ]);
        }
    }

    private function normalizeFacultyLimits(array $names, array $limits): array
    {
        $items = [];
        foreach ($names as $index => $name) {
            $cleanName = trim($name);
            if ($cleanName === '') {
                continue;
            }
            $limitValue = $limits[$index] ?? null;
            if ($limitValue === null || $limitValue === '') {
                continue;
            }
            $items[] = [
                'faculty_name' => $cleanName,
                'limit' => (int) $limitValue,
            ];
        }
        return $items;
    }

    private function storeFacultyLimits(Event $event, array $limits): void
    {
        $event->facultyLimits()->delete();
        foreach ($limits as $limit) {
            $event->facultyLimits()->create([
                'faculty_name' => $limit['faculty_name'],
                'limit' => $limit['limit'],
            ]);
        }
    }

    public function validateCommittee(Request $request)
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            abort(403);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'string', 'max:255'],
        ]);

        $exists = User::where('student_id', $validated['student_id'])
            ->where('role', 'student')
            ->exists();

        return response()->json([
            'valid' => $exists,
            'message' => $exists ? 'OK' : 'Student ID not found.',
        ]);
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            abort(403);
        }

        $events = Event::where('club_id', $user->id)
            ->latest()
            ->get();

        return view('club.events.index', [
            'events' => $events,
        ]);
    }

    public function create(Request $request): View
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            abort(403);
        }

        return view('club.events.create');
    }

    public function show(Event $event): View
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            abort(403);
        }

        if ($event->club_id !== $user->id) {
            abort(403);
        }

        $event->load(['committeeMembers', 'subEvents', 'facultyLimits']);

        return view('club.events.show', [
            'event' => $event,
        ]);
    }

    public function edit(Event $event): View
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            abort(403);
        }

        if ($event->club_id !== $user->id) {
            abort(403);
        }

        $event->load(['subEvents', 'facultyLimits']);
        $committeeIds = $event->committeeMembers()
            ->pluck('student_id')
            ->all();

        return view('club.events.edit', [
            'event' => $event,
            'committeeIds' => $committeeIds ? implode(', ', $committeeIds) : null,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:255'],
            'participant_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'committee_student_ids' => ['nullable', 'string', 'max:2000'],
            'sub_event_title' => ['nullable', 'array'],
            'sub_event_title.*' => ['nullable', 'string', 'max:255'],
            'sub_event_date' => ['nullable', 'array'],
            'sub_event_date.*' => ['nullable', 'date'],
            'faculty_name' => ['nullable', 'array'],
            'faculty_name.*' => ['nullable', 'string', 'max:255'],
            'faculty_limit' => ['nullable', 'array'],
            'faculty_limit.*' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:5120'],
        ]);

        $committeeIds = $this->parseCommitteeIds($validated['committee_student_ids'] ?? null);
        $committeeUserIds = $this->resolveCommitteeUsers($committeeIds);
        $subEvents = $this->normalizeSubEvents(
            $validated['sub_event_title'] ?? [],
            $validated['sub_event_date'] ?? []
        );
        $facultyLimits = $this->normalizeFacultyLimits(
            $validated['faculty_name'] ?? [],
            $validated['faculty_limit'] ?? []
        );

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('event-logos', 'public');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('event-files', 'public');
        }

        $event = Event::create([
            'club_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'participant_limit' => $validated['participant_limit'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'logo_path' => $logoPath,
            'attachment_path' => $attachmentPath,
        ]);

        $event->committeeMembers()->sync($committeeUserIds);
        $this->storeSubEvents($event, $subEvents);
        $this->storeFacultyLimits($event, $facultyLimits);

        return redirect()->route('club.events.index')->with('status', 'Event submitted.');
    }

    public function update(Request $request, Event $event)
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'club') {
            abort(403);
        }

        if ($event->club_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:255'],
            'participant_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'committee_student_ids' => ['nullable', 'string', 'max:2000'],
            'sub_event_title' => ['nullable', 'array'],
            'sub_event_title.*' => ['nullable', 'string', 'max:255'],
            'sub_event_date' => ['nullable', 'array'],
            'sub_event_date.*' => ['nullable', 'date'],
            'faculty_name' => ['nullable', 'array'],
            'faculty_name.*' => ['nullable', 'string', 'max:255'],
            'faculty_limit' => ['nullable', 'array'],
            'faculty_limit.*' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:5120'],
        ]);

        $committeeIds = $this->parseCommitteeIds($validated['committee_student_ids'] ?? null);
        $committeeUserIds = $this->resolveCommitteeUsers($committeeIds);
        $subEvents = $this->normalizeSubEvents(
            $validated['sub_event_title'] ?? [],
            $validated['sub_event_date'] ?? []
        );
        $facultyLimits = $this->normalizeFacultyLimits(
            $validated['faculty_name'] ?? [],
            $validated['faculty_limit'] ?? []
        );

        $logoPath = $event->logo_path;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('event-logos', 'public');
        }

        $attachmentPath = $event->attachment_path;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('event-files', 'public');
        }

        $event->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'participant_limit' => $validated['participant_limit'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'logo_path' => $logoPath,
            'attachment_path' => $attachmentPath,
        ]);

        $event->committeeMembers()->sync($committeeUserIds);
        $this->storeSubEvents($event, $subEvents);
        $this->storeFacultyLimits($event, $facultyLimits);

        return redirect()
            ->route('club.events.show', $event)
            ->with('status', 'Event updated.');
    }
}
