<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Models\BuddyContinuation;
use App\Models\BuddyMatch;
use App\Models\BuddyParticipant;
use App\Models\BuddySemesterSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContinuationController extends Controller
{
    // ── Shared helper: load last-semester participant for authenticated user ──
    private function lastParticipant(?string $role = null, ?BuddySemesterSetting $excludeSemester = null): ?BuddyParticipant
    {
        return BuddyParticipant::where('user_id', Auth::id())
            ->when($role, fn($q) => $q->where('role', $role))
            ->whereNotNull('semester_id')
            ->when($excludeSemester, fn($q) => $q->where('semester_id', '!=', $excludeSemester->id))
            ->with('semester')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Mentee records their continuation decision (continue or decline).
     * POST /api/buddy/continuation/mentee-choice
     * Body: { choice: 'continue' | 'decline' }
     */
    public function menteeChoice(Request $request): JsonResponse
    {
        $request->validate(['choice' => 'required|in:continue,decline']);

        $active = BuddySemesterSetting::getActiveSemester();
        $last   = $this->lastParticipant('mentee', $active);

        if (!$last) {
            return response()->json(['success' => false, 'message' => 'No previous mentee participation found.'], 404);
        }

        if ($request->choice === 'decline') {
            $last->update(['continuation_choice' => 'declined']);
            return response()->json(['success' => true, 'data' => [
                'state'       => 'dashboard_readonly',
                'semester_id' => $last->semester_id,
            ]]);
        }

        // 'continue' — requires an active next semester
        if (!$active) {
            return response()->json(['success' => false, 'message' => 'No active semester to continue into.'], 422);
        }

        DB::beginTransaction();
        try {
            $matches = BuddyMatch::where('semester_id', $last->semester_id)
                ->where('status', 'active')
                ->whereHas('participants', fn($q) => $q
                    ->where('buddy_match_participants.participant_id', $last->id)
                    ->where('buddy_match_participants.role', 'mentee'))
                ->with('participants')
                ->get();

            $continuations = [];

            foreach ($matches as $match) {
                $mentors = $match->participants()->wherePivot('role', 'mentor')->get();

                foreach ($mentors as $mentor) {
                    // If the mentor already declined continuation, auto-mark as declined
                    $mentorDeclined = $mentor->continuation_choice === 'declined';

                    $cont = BuddyContinuation::firstOrCreate(
                        [
                            'match_id'              => $match->id,
                            'to_semester_id'        => $active->id,
                            'mentee_participant_id' => $last->id,
                            'mentor_participant_id' => $mentor->id,
                        ],
                        [
                            'from_semester_id' => $last->semester_id,
                            'mentee_choice'    => 'continue',
                            'mentor_choice'    => $mentorDeclined ? 'decline' : 'pending',
                            'resolved_at'      => $mentorDeclined ? now() : null,
                        ]
                    );

                    if (!$cont->wasRecentlyCreated) {
                        $cont->update(['mentee_choice' => 'continue']);
                    }

                    $continuations[] = [
                        'id'            => $cont->id,
                        'mentor_name'   => $mentor->full_name,
                        'mentor_choice' => $cont->mentor_choice,
                    ];
                }
            }

            $last->update(['continuation_choice' => 'continued']);

            DB::commit();

            return response()->json(['success' => true, 'data' => [
                'state'         => 'waiting_for_mentor',
                'continuations' => $continuations,
            ]]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mentor retrieves all pending continuation requests from their mentees.
     * GET /api/buddy/continuation/mentor-requests
     */
    public function getMentorRequests(Request $request): JsonResponse
    {
        $active = BuddySemesterSetting::getActiveSemester();
        $last   = $this->lastParticipant('mentor', $active);

        if (!$last) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $requests = BuddyContinuation::where('mentor_participant_id', $last->id)
            ->where('mentee_choice', 'continue')
            ->when($active, fn($q) => $q->where('to_semester_id', $active->id))
            ->with(['menteeParticipant.subject', 'match.subject'])
            ->get()
            ->map(fn($c) => [
                'id'                => $c->id,
                'mentee_name'       => $c->menteeParticipant?->full_name,
                'mentee_student_id' => $c->menteeParticipant?->student_id,
                'subject_name'      => $c->match?->subject?->name ?? $c->menteeParticipant?->subject?->name,
                'mentor_choice'     => $c->mentor_choice,
                'resolved_at'       => $c->resolved_at,
            ]);

        return response()->json(['success' => true, 'data' => $requests]);
    }

    /**
     * Mentor accepts or declines an individual mentee's continuation request.
     * POST /api/buddy/continuation/mentor-response
     * Body: { continuation_id, choice: 'continue' | 'decline', new_subject_id? }
     */
    public function mentorResponse(Request $request): JsonResponse
    {
        $request->validate([
            'continuation_id' => 'required|integer|exists:buddy_continuations,id',
            'choice'          => 'required|in:continue,decline',
        ]);

        $active       = BuddySemesterSetting::getActiveSemester();
        $continuation = BuddyContinuation::with([
            'menteeParticipant', 'mentorParticipant', 'match.subject',
        ])->find($request->continuation_id);

        // Verify ownership
        $ownerParticipant = BuddyParticipant::where('user_id', Auth::id())
            ->where('id', $continuation->mentor_participant_id)
            ->first();

        if (!$ownerParticipant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($continuation->mentor_choice !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This request has already been resolved.'], 422);
        }

        DB::beginTransaction();
        try {
            if ($request->choice === 'decline') {
                $continuation->update(['mentor_choice' => 'decline', 'resolved_at' => now()]);
                DB::commit();
                return response()->json(['success' => true, 'data' => ['mentor_choice' => 'decline']]);
            }

            // 'continue'
            if (!$active) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'No active semester.'], 422);
            }

            $subjectId  = $continuation->match?->subject_id;
            $prevMentee = $continuation->menteeParticipant;

            // Enforce max-3-mentees cap for mentor in new semester
            $menteeCount = BuddyMatch::where('semester_id', $active->id)
                ->where('status', 'active')
                ->whereHas('participants', fn($q) => $q
                    ->where('buddy_match_participants.participant_id', $ownerParticipant->id)
                    ->where('buddy_match_participants.role', 'mentor'))
                ->withCount('mentees')
                ->get()
                ->sum('mentees_count');

            if ($menteeCount >= 3) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Mentor has reached the maximum of 3 mentees.'], 422);
            }

            // Carry forward (or reuse existing) new-semester mentor participant
            $newMentor = BuddyParticipant::firstOrCreate(
                ['user_id' => $ownerParticipant->user_id, 'semester_id' => $active->id],
                array_merge(
                    $ownerParticipant->only([
                        'full_name', 'student_id', 'course', 'faculty', 'year_of_study',
                        'cgpa', 'role', 'is_repeater', 'document_path', 'document_name',
                        'rating', 'priority_tier',
                    ]),
                    ['status' => 'active', 'subject_id' => $subjectId, 'continuation_choice' => 'continued']
                )
            );

            // Carry forward (or reuse existing) new-semester mentee participant
            $newMentee = BuddyParticipant::firstOrCreate(
                ['user_id' => $prevMentee->user_id, 'semester_id' => $active->id],
                array_merge(
                    $prevMentee->only([
                        'full_name', 'student_id', 'course', 'faculty', 'year_of_study',
                        'cgpa', 'role', 'is_repeater', 'document_path', 'document_name',
                        'rating', 'priority_tier',
                    ]),
                    ['status' => 'active', 'subject_id' => $subjectId, 'continuation_choice' => 'continued']
                )
            );

            // Create a new match for this semester
            $newMatch = BuddyMatch::create([
                'semester_id'        => $active->id,
                'mentor_id'          => $newMentor->id,
                'mentee_id'          => $newMentee->id,
                'subject_id'         => $subjectId,
                'status'             => 'active',
                'matched_date'       => now(),
                'total_sessions'     => 0,
                'completed_sessions' => 0,
            ]);

            $newMatch->participants()->attach($newMentor->id, ['role' => 'mentor']);
            $newMatch->participants()->attach($newMentee->id, ['role' => 'mentee']);

            $continuation->update([
                'mentor_choice'  => 'continue',
                'resolved_at'    => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'data' => [
                'mentor_choice' => 'continue',
                'new_match_id'  => $newMatch->id,
            ]]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mentor with no pending mentee requests makes their own choice.
     * POST /api/buddy/continuation/mentor-self-choice
     * Body: { choice: 'continue' | 'decline' }
     * 'continue' → the mentor will be prompted to re-register fresh
     * 'decline'  → mark as declined (read-only access to last semester)
     */
    public function mentorSelfChoice(Request $request): JsonResponse
    {
        $request->validate(['choice' => 'required|in:continue,decline']);

        $active = BuddySemesterSetting::getActiveSemester();
        $last   = $this->lastParticipant('mentor', $active);

        if (!$last) {
            return response()->json(['success' => false, 'message' => 'No previous mentor record found.'], 404);
        }

        if ($request->choice === 'decline') {
            $last->update(['continuation_choice' => 'declined']);

            // Auto-decline all pending continuation requests from mentees for this mentor
            if ($active) {
                BuddyContinuation::where('mentor_participant_id', $last->id)
                    ->where('to_semester_id', $active->id)
                    ->where('mentor_choice', 'pending')
                    ->update(['mentor_choice' => 'decline', 'resolved_at' => now()]);
            }

            return response()->json(['success' => true, 'data' => [
                'state'       => 'dashboard_readonly',
                'semester_id' => $last->semester_id,
            ]]);
        }

        // 'continue' — auto-carry forward mentor to new semester (skip admin re-approval)
        if (!$active) {
            return response()->json(['success' => false, 'message' => 'No active semester to continue into.'], 422);
        }

        $last->update(['continuation_choice' => 'continued']);

        BuddyParticipant::firstOrCreate(
            ['user_id' => $last->user_id, 'semester_id' => $active->id],
            array_merge(
                $last->only([
                    'full_name', 'student_id', 'course', 'faculty', 'year_of_study',
                    'cgpa', 'role', 'is_repeater', 'document_path', 'document_name',
                    'rating', 'priority_tier', 'subject_id',
                ]),
                ['status' => 'active', 'continuation_choice' => 'continued']
            )
        );

        return response()->json(['success' => true, 'data' => [
            'state'       => 'pending_match',
            'semester_id' => $active->id,
        ]]);
    }
}
