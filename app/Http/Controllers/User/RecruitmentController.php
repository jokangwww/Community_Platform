<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Recruitment;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentApplicationAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecruitmentController extends Controller
{
    private function requireStudent()
    {
        $user = Auth::user();
        if (! $user || $user->role !== 'student') {
            abort(403);
        }

        return $user;
    }

    public function index(Request $request)
    {
        $user = $this->requireStudent();

        $keyword = $request->query('q');
        $skills = $request->query('skills');
        $interests = $request->query('interests');
        $status = $request->query('status');

        $recruitments = Recruitment::with(['event', 'club'])
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($sub) use ($keyword) {
                    $sub->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%')
                        ->orWhere('requirements', 'like', '%' . $keyword . '%')
                        ->orWhere('required_skills', 'like', '%' . $keyword . '%')
                        ->orWhere('interests', 'like', '%' . $keyword . '%');
                });
            })
            ->when($skills, function ($query) use ($skills) {
                $query->where('required_skills', 'like', '%' . $skills . '%');
            })
            ->when($interests, function ($query) use ($interests) {
                $query->where('interests', 'like', '%' . $interests . '%');
            })
            ->when($status, function ($query) use ($user, $status) {
                $query->whereHas('applications', function ($sub) use ($user, $status) {
                    $sub->where('student_id', $user->id)
                        ->where('status', $status);
                });
            })
            ->latest()
            ->get();

        return view('user.recruitment', [
            'recruitments' => $recruitments,
            'filters' => [
                'q' => $keyword,
                'skills' => $skills,
                'interests' => $interests,
                'status' => $status,
            ],
        ]);
    }

    public function show(Recruitment $recruitment)
    {
        $user = $this->requireStudent();

        $recruitment->load(['event', 'club', 'questions']);

        $application = RecruitmentApplication::where('recruitment_id', $recruitment->id)
            ->where('student_id', $user->id)
            ->first();
        $applied = (bool) $application;

        return view('user.recruitment-show', [
            'recruitment' => $recruitment,
            'applied' => $applied,
            'application' => $application,
        ]);
    }

    public function submitted()
    {
        $user = $this->requireStudent();

        $applications = RecruitmentApplication::with(['recruitment.event', 'recruitment.club'])
            ->where('student_id', $user->id)
            ->latest()
            ->get();

        return view('user.recruitment-submitted', [
            'applications' => $applications,
        ]);
    }

    public function apply(Request $request, Recruitment $recruitment)
    {
        $user = $this->requireStudent();

        $recruitment->load('questions');

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
            'skills' => ['nullable', 'string', 'max:255'],
            'experience' => ['nullable', 'string', 'max:2000'],
            'answer' => ['nullable', 'array'],
            'answer.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $application = RecruitmentApplication::firstOrCreate(
            [
                'recruitment_id' => $recruitment->id,
                'student_id' => $user->id,
            ],
            [
                'phone' => $validated['phone'] ?? null,
                'skills' => $validated['skills'] ?? null,
                'experience' => $validated['experience'] ?? null,
            ]
        );

        $application->update([
            'phone' => $validated['phone'] ?? null,
            'skills' => $validated['skills'] ?? null,
            'experience' => $validated['experience'] ?? null,
        ]);

        $application->answers()->delete();
        $answers = $validated['answer'] ?? [];
        foreach ($recruitment->questions as $index => $question) {
            $value = trim($answers[$index] ?? '');
            if ($value === '') {
                continue;
            }
            $application->answers()->create([
                'recruitment_question_id' => $question->id,
                'answer' => $value,
            ]);
        }

        return redirect()
            ->route('user.recruitment.show', $recruitment)
            ->with('status', 'Application submitted.');
    }
}
