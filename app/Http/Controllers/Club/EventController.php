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
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:5120'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('event-files', 'public');
        }

        Event::create([
            'club_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'attachment_path' => $attachmentPath,
        ]);

        return redirect()->route('club.events.index')->with('status', 'Event submitted.');
    }
}
