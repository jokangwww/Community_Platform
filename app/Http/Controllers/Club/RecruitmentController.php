<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Recruitment;
use App\Models\RecruitmentQuestion;
use App\Models\RecruitmentApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecruitmentController extends Controller
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
        $this->requireClub();

        $recruitments = Recruitment::with(['event', 'club'])
            ->latest()
            ->get();

        return view('club.recruitment', [
            'recruitments' => $recruitments,
            'activeTab' => 'all',
        ]);
    }

    public function mine()
    {
        $user = $this->requireClub();

        $recruitments = Recruitment::with(['event', 'club'])
            ->where('club_id', $user->id)
            ->latest()
            ->get();

        return view('club.recruitment', [
            'recruitments' => $recruitments,
            'activeTab' => 'mine',
        ]);
    }

    public function create()
    {
        $user = $this->requireClub();

        $events = Event::where('club_id', $user->id)
            ->orderBy('name')
            ->get();

        return view('club.recruitment-create', compact('events'));
    }

    public function store(Request $request)
    {
        $user = $this->requireClub();

        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'requirements' => ['nullable', 'string', 'max:2000'],
            'required_skills' => ['nullable', 'string', 'max:255'],
            'interests' => ['nullable', 'string', 'max:255'],
            'question' => ['nullable', 'array'],
            'question.*' => ['nullable', 'string', 'max:255'],
        ]);

        $event = Event::where('id', $validated['event_id'])
            ->where('club_id', $user->id)
            ->firstOrFail();

        $recruitment = Recruitment::create([
            'club_id' => $user->id,
            'event_id' => $event->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'requirements' => $validated['requirements'] ?? null,
            'required_skills' => $validated['required_skills'] ?? null,
            'interests' => $validated['interests'] ?? null,
        ]);

        $questions = array_values(array_filter(array_map('trim', $validated['question'] ?? [])));
        foreach ($questions as $index => $question) {
            $recruitment->questions()->create([
                'question' => $question,
                'position' => $index,
            ]);
        }

        return redirect()
            ->route('club.recruitment.mine')
            ->with('status', 'Recruitment created.');
    }

    public function show(Request $request, Recruitment $recruitment)
    {
        $user = $this->requireClub();

        if ($recruitment->club_id !== $user->id) {
            abort(403);
        }

        $skills = $request->query('skills');
        $experience = $request->query('experience');
        $status = $request->query('status');
        $allowedStatuses = ['pending', 'accepted', 'rejected'];
        $statusFilter = in_array($status, $allowedStatuses, true) ? $status : null;

        $applications = RecruitmentApplication::with(['student', 'answers'])
            ->where('recruitment_id', $recruitment->id)
            ->when($skills, function ($query) use ($skills) {
                $query->where('skills', 'like', '%' . $skills . '%');
            })
            ->when($experience, function ($query) use ($experience) {
                $query->where('experience', 'like', '%' . $experience . '%');
            })
            ->when($statusFilter, function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->latest()
            ->get();

        $recruitment->load(['event', 'questions']);

        return view('club.recruitment-show', [
            'recruitment' => $recruitment,
            'applications' => $applications,
            'filters' => [
                'skills' => $skills,
                'experience' => $experience,
                'status' => $statusFilter,
            ],
        ]);
    }

    public function edit(Recruitment $recruitment)
    {
        $user = $this->requireClub();

        if ($recruitment->club_id !== $user->id) {
            abort(403);
        }

        $events = Event::where('club_id', $user->id)
            ->orderBy('name')
            ->get();

        $recruitment->load('questions');

        return view('club.recruitment-edit', [
            'recruitment' => $recruitment,
            'events' => $events,
        ]);
    }

    public function update(Request $request, Recruitment $recruitment)
    {
        $user = $this->requireClub();

        if ($recruitment->club_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'requirements' => ['nullable', 'string', 'max:2000'],
            'required_skills' => ['nullable', 'string', 'max:255'],
            'interests' => ['nullable', 'string', 'max:255'],
            'question' => ['nullable', 'array'],
            'question.*' => ['nullable', 'string', 'max:255'],
        ]);

        $event = Event::where('id', $validated['event_id'])
            ->where('club_id', $user->id)
            ->firstOrFail();

        $recruitment->update([
            'event_id' => $event->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'requirements' => $validated['requirements'] ?? null,
            'required_skills' => $validated['required_skills'] ?? null,
            'interests' => $validated['interests'] ?? null,
        ]);

        $recruitment->questions()->delete();
        $questions = array_values(array_filter(array_map('trim', $validated['question'] ?? [])));
        foreach ($questions as $index => $question) {
            $recruitment->questions()->create([
                'question' => $question,
                'position' => $index,
            ]);
        }

        return redirect()
            ->route('club.recruitment.show', $recruitment)
            ->with('status', 'Recruitment updated.');
    }

    public function destroy(Recruitment $recruitment)
    {
        $user = $this->requireClub();

        if ($recruitment->club_id !== $user->id) {
            abort(403);
        }

        $recruitment->delete();

        return redirect()
            ->route('club.recruitment.mine')
            ->with('status', 'Recruitment deleted.');
    }

    public function updateApplication(Request $request, Recruitment $recruitment, RecruitmentApplication $application)
    {
        $user = $this->requireClub();

        if ($recruitment->club_id !== $user->id || $application->recruitment_id !== $recruitment->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,accepted,rejected'],
            'reply' => ['nullable', 'string', 'max:2000'],
        ]);

        $application->update([
            'status' => $validated['status'],
            'reply' => $validated['reply'] ?? null,
        ]);

        return redirect()
            ->route('club.recruitment.show', $recruitment)
            ->with('status', 'Application updated.');
    }
}
