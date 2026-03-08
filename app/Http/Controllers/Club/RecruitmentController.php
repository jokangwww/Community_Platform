<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Recruitment;
use App\Models\RecruitmentQuestion;
use App\Models\RecruitmentApplication;
use App\Models\User;
use Illuminate\Http\Request;

class RecruitmentController extends Controller
{
    // Resolve the authenticated club user for recruitment ownership checks.
    private function requireClub(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    // Recruitment listing tab showing all active recruitments across clubs.
    public function index()
    {
        $this->requireClub();

        $recruitments = Recruitment::with(['event', 'club'])
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended');
            })
            ->latest()
            ->get();

        return view('club.recruitment', [
            'recruitments' => $recruitments,
            'activeTab' => 'all',
        ]);
    }

    // Recruitment listing tab showing only recruitments created by the current club.
    public function mine()
    {
        $user = $this->requireClub();

        $recruitments = Recruitment::with(['event', 'club'])
            ->where('club_id', $user->id)
            ->whereHas('event', function ($query) {
                $query->where('status', '!=', 'ended');
            })
            ->latest()
            ->get();

        return view('club.recruitment', [
            'recruitments' => $recruitments,
            'activeTab' => 'mine',
        ]);
    }

    // Recruitment create form loads the club's active events for linking recruitment to an event.
    public function create()
    {
        $user = $this->requireClub();

        $events = Event::where('club_id', $user->id)
            ->where('status', '!=', 'ended')
            ->orderBy('name')
            ->get();

        return view('club.recruitment-create', compact('events'));
    }

    // Create recruitment and persist dynamic interview/application questions.
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
            'volunteer_benefits' => ['nullable', 'string', 'max:2000'],
            'question' => ['nullable', 'array'],
            'question.*' => ['nullable', 'string', 'max:255'],
        ]);

        // Only allow recruitments to be attached to this club's non-ended events.
        $event = Event::where('id', $validated['event_id'])
            ->where('club_id', $user->id)
            ->where('status', '!=', 'ended')
            ->firstOrFail();

        $recruitment = Recruitment::create([
            'club_id' => $user->id,
            'event_id' => $event->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'requirements' => $validated['requirements'] ?? null,
            'required_skills' => $validated['required_skills'] ?? null,
            'interests' => $validated['interests'] ?? null,
            'volunteer_benefits' => $validated['volunteer_benefits'] ?? null,
        ]);

        // Save optional question rows in submitted order for application forms.
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

    // Recruitment detail page shows applicants with filters and application answers.
    public function show(Request $request, Recruitment $recruitment)
    {
        $user = $this->requireClub();

        if ($recruitment->club_id !== $user->id) {
            abort(403);
        }
        if (($recruitment->event?->status ?? 'in_progress') === 'ended') {
            abort(404);
        }

        // Applicant filters support skill keywords, experience keywords, and application status.
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

    // Recruitment edit form with existing question list.
    public function edit(Recruitment $recruitment)
    {
        $user = $this->requireClub();

        if ($recruitment->club_id !== $user->id) {
            abort(403);
        }

        $events = Event::where('club_id', $user->id)
            ->where('status', '!=', 'ended')
            ->orderBy('name')
            ->get();

        $recruitment->load('questions');

        return view('club.recruitment-edit', [
            'recruitment' => $recruitment,
            'events' => $events,
        ]);
    }

    // Update recruitment details and fully replace the configured application questions.
    public function update(Request $request, Recruitment $recruitment)
    {
        $user = $this->requireClub();

        if ($recruitment->club_id !== $user->id) {
            abort(403);
        }
        if (($recruitment->event?->status ?? 'in_progress') === 'ended') {
            abort(404);
        }

        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'requirements' => ['nullable', 'string', 'max:2000'],
            'required_skills' => ['nullable', 'string', 'max:255'],
            'interests' => ['nullable', 'string', 'max:255'],
            'volunteer_benefits' => ['nullable', 'string', 'max:2000'],
            'question' => ['nullable', 'array'],
            'question.*' => ['nullable', 'string', 'max:255'],
        ]);

        $event = Event::where('id', $validated['event_id'])
            ->where('club_id', $user->id)
            ->where('status', '!=', 'ended')
            ->firstOrFail();

        $recruitment->update([
            'event_id' => $event->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'requirements' => $validated['requirements'] ?? null,
            'required_skills' => $validated['required_skills'] ?? null,
            'interests' => $validated['interests'] ?? null,
            'volunteer_benefits' => $validated['volunteer_benefits'] ?? null,
        ]);

        // Rebuild question list to match the current form rows exactly.
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

    // Delete a club-owned recruitment posting.
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

    // Organizer review action for applications (pending/accepted/rejected + optional reply).
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
