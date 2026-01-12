<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EventController extends Controller
{
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

        return view('club.events.edit', [
            'event' => $event,
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
            'logo' => ['nullable', 'image', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:5120'],
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('event-logos', 'public');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('event-files', 'public');
        }

        Event::create([
            'club_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'logo_path' => $logoPath,
            'attachment_path' => $attachmentPath,
        ]);

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
            'logo' => ['nullable', 'image', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:5120'],
        ]);

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
            'logo_path' => $logoPath,
            'attachment_path' => $attachmentPath,
        ]);

        return redirect()
            ->route('club.events.show', $event)
            ->with('status', 'Event updated.');
    }
}
