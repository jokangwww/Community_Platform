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

class EntryStateController extends Controller
{
    /**
     * Determine which screen to show the authenticated user on load.
     *
     * Returned state strings (consumed by BuddyEntryGuard.tsx):
     *   new_user                    – never registered; show role-selection / register form
     *   pending_review              – registered in active semester, awaiting admin approval
     *   pending_match               – approved in active semester, no match yet
     *   dashboard                   – active semester, has active match
     *   continue_prompt             – semester ended, mentee hasn't answered continuation yet
     *   waiting_for_mentor          – mentee chose "continue", mentor hasn't responded yet
     *   mentor_declined             – mentor declined mentee's continuation request
     *   mentor_continuation_choices – mentor has pending mentee continuation requests to resolve
     *   dashboard_readonly          – viewing a past semester (declined or explicit navigation)
     */
    public function getEntryState(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $activeSemester = BuddySemesterSetting::getActiveSemester();

        // ── Check active-semester participant first ───────────────────────────
        if ($activeSemester) {
            $participant = BuddyParticipant::where('user_id', $userId)
                ->where('semester_id', $activeSemester->id)
                ->first();

            if ($participant) {
                return $this->resolveCurrentParticipantState($participant, $activeSemester);
            }
        }

        // ── Check for participant registered without a semester ───────────────
        // This happens when a user registers while no active semester is configured.
        $unassignedParticipant = BuddyParticipant::where('user_id', $userId)
            ->whereNull('semester_id')
            ->orderByDesc('created_at')
            ->first();

        if ($unassignedParticipant) {
            // Back-fill the semester_id now if an active semester exists.
            // IMPORTANT: If the participant was created BEFORE the active semester started,
            // they belong to the most recent PAST semester, not the new one.
            if ($activeSemester) {
                $createdAt = $unassignedParticipant->created_at;
                $semesterStart = \Carbon\Carbon::parse($activeSemester->start_date);

                if ($createdAt && $createdAt->lt($semesterStart)) {
                    // Participant predates the active semester — assign to closest past semester
                    $pastSemester = BuddySemesterSetting::where('is_active', false)
                        ->orderByDesc('end_date')
                        ->first();
                    $assignSemesterId = $pastSemester ? $pastSemester->id : $activeSemester->id;
                } else {
                    $assignSemesterId = $activeSemester->id;
                }

                $unassignedParticipant->update(['semester_id' => $assignSemesterId]);

                // If assigned to a past semester, treat as returning user (continuation flow)
                if ($assignSemesterId !== $activeSemester->id) {
                    $unassignedParticipant->refresh();
                    $unassignedParticipant->load('semester');

                    if ($unassignedParticipant->continuation_choice === 'declined') {
                        return response()->json(['success' => true, 'data' => [
                            'state'              => 'dashboard_readonly',
                            'participant'        => $this->participantSummary($unassignedParticipant),
                            'semester'           => $unassignedParticipant->semester?->toSettingsArray(),
                            'has_multiple_roles' => $this->hasMultipleRoles($userId),
                        ]]);
                    }

                    if ($unassignedParticipant->role === 'mentee') {
                        return $this->menteeEntryState($unassignedParticipant, $activeSemester);
                    }
                    if ($unassignedParticipant->role === 'mentor') {
                        return $this->mentorEntryState($unassignedParticipant, $activeSemester);
                    }
                }
            }
            return $this->resolveCurrentParticipantState($unassignedParticipant, $activeSemester);
        }

        // ── No current participant — check past semesters ────────────────────
        $lastParticipant = BuddyParticipant::where('user_id', $userId)
            ->whereNotNull('semester_id')
            ->with('semester')
            ->orderByDesc('created_at')
            ->first();

        if (!$lastParticipant) {
            return response()->json(['success' => true, 'data' => ['state' => 'new_user']]);
        }

        // Explicitly declined → read-only
        if ($lastParticipant->continuation_choice === 'declined') {
            return response()->json(['success' => true, 'data' => [
                'state'              => 'dashboard_readonly',
                'participant'        => $this->participantSummary($lastParticipant),
                'semester'           => $lastParticipant->semester?->toSettingsArray(),
                'has_multiple_roles' => $this->hasMultipleRoles($userId),
            ]]);
        }

        if ($lastParticipant->role === 'mentee') {
            return $this->menteeEntryState($lastParticipant, $activeSemester);
        }

        if ($lastParticipant->role === 'mentor') {
            return $this->mentorEntryState($lastParticipant, $activeSemester);
        }

        return response()->json(['success' => true, 'data' => ['state' => 'new_user']]);
    }

    // ── Mentee-specific logic ─────────────────────────────────────────────────
    private function menteeEntryState(BuddyParticipant $last, ?BuddySemesterSetting $active): JsonResponse
    {
        $continuation = BuddyContinuation::where('mentee_participant_id', $last->id)
            ->when($active, fn($q) => $q->where('to_semester_id', $active->id))
            ->latest()
            ->first();

        if (!$continuation) {
            // Before showing continue_prompt, check if ALL the mentee's mentors
            // from the previous semester have already declined continuation.
            // If so, skip the prompt and show mentor_declined directly.
            $mentorIds = BuddyMatch::where('semester_id', $last->semester_id)
                ->where('status', 'active')
                ->whereHas('participants', fn($q) => $q
                    ->where('buddy_match_participants.participant_id', $last->id)
                    ->where('buddy_match_participants.role', 'mentee'))
                ->get()
                ->flatMap(fn($m) => $m->participants()->wherePivot('role', 'mentor')->pluck('buddy_participants.id'));

            if ($mentorIds->isNotEmpty()) {
                $allMentorsDeclined = BuddyParticipant::whereIn('id', $mentorIds)
                    ->where('continuation_choice', 'declined')
                    ->count() === $mentorIds->count();

                if ($allMentorsDeclined) {
                    return response()->json(['success' => true, 'data' => [
                        'state'       => 'mentor_declined',
                        'participant' => $this->participantSummary($last),
                        'semester'    => $active?->toSettingsArray(),
                        'continuations' => $mentorIds->map(fn($id) => [
                            'id'            => 0,
                            'mentor_name'   => BuddyParticipant::find($id)?->full_name,
                            'mentor_choice' => 'decline',
                        ])->values(),
                    ]]);
                }
            }

            return response()->json(['success' => true, 'data' => [
                'state'          => 'continue_prompt',
                'participant'    => $this->participantSummary($last),
                'semester'       => $last->semester?->toSettingsArray(),
                'next_semester'  => $active?->toSettingsArray(),
            ]]);
        }

        if ($continuation->mentee_choice === 'decline') {
            return response()->json(['success' => true, 'data' => [
                'state'              => 'dashboard_readonly',
                'participant'        => $this->participantSummary($last),
                'semester'           => $last->semester?->toSettingsArray(),
                'has_multiple_roles' => $this->hasMultipleRoles($last->user_id),
            ]]);
        }

        // mentee_choice === 'continue' — check all mentor responses
        $allContinations = BuddyContinuation::where('mentee_participant_id', $last->id)
            ->when($active, fn($q) => $q->where('to_semester_id', $active->id))
            ->with('mentorParticipant')
            ->get();

        $anyDeclined = $allContinations->contains(fn($c) => $c->mentor_choice === 'decline');
        $anyPending  = $allContinations->contains(fn($c) => $c->mentor_choice === 'pending');

        if ($anyDeclined) {
            return response()->json(['success' => true, 'data' => [
                'state'         => 'mentor_declined',
                'participant'   => $this->participantSummary($last),
                'semester'      => $active?->toSettingsArray(),
                'continuations' => $allContinations->map(fn($c) => [
                    'id'            => $c->id,
                    'mentor_name'   => $c->mentorParticipant?->full_name,
                    'mentor_choice' => $c->mentor_choice,
                ])->values(),
            ]]);
        }

        if ($anyPending) {
            return response()->json(['success' => true, 'data' => [
                'state'       => 'waiting_for_mentor',
                'participant' => $this->participantSummary($last),
                'semester'    => $last->semester?->toSettingsArray(),
            ]]);
        }

        // All mentors continued — new match should exist (active semester participant should have been found above)
        return response()->json(['success' => true, 'data' => [
            'state'   => 'dashboard',
            'semester' => $active?->toSettingsArray(),
        ]]);
    }

    // ── Mentor-specific logic ─────────────────────────────────────────────────
    private function mentorEntryState(BuddyParticipant $last, ?BuddySemesterSetting $active): JsonResponse
    {
        if ($last->continuation_choice === 'declined') {
            return response()->json(['success' => true, 'data' => [
                'state'              => 'dashboard_readonly',
                'participant'        => $this->participantSummary($last),
                'semester'           => $last->semester?->toSettingsArray(),
                'has_multiple_roles' => $this->hasMultipleRoles($last->user_id),
            ]]);
        }

        $continuations = BuddyContinuation::where('mentor_participant_id', $last->id)
            ->when($active, fn($q) => $q->where('to_semester_id', $active->id))
            ->get();

        $pendingCount = $continuations
            ->where('mentee_choice', 'continue')
            ->where('mentor_choice', 'pending')
            ->count();

        if ($pendingCount > 0) {
            return response()->json(['success' => true, 'data' => [
                'state'         => 'mentor_continuation_choices',
                'participant'   => $this->participantSummary($last),
                'semester'      => $last->semester?->toSettingsArray(),
                'next_semester' => $active?->toSettingsArray(),
                'pending_count' => $pendingCount,
            ]]);
        }

        if ($continuations->isNotEmpty()) {
            $acceptedCount = $continuations->where('mentor_choice', 'continue')->count();

            if ($acceptedCount > 0 || $continuations->every(fn($continuation) => $continuation->mentor_choice === 'decline')) {
                return response()->json(['success' => true, 'data' => [
                    'state'              => 'dashboard_readonly',
                    'participant'        => $this->participantSummary($last),
                    'semester'           => $last->semester?->toSettingsArray(),
                    'has_multiple_roles' => $this->hasMultipleRoles($last->user_id),
                ]]);
            }
        }

        // Check if mentor had active matches in old semester — if so, show
        // continuation choices (self-choice panel) instead of forcing re-registration
        // which would require unnecessary admin re-approval.
        $hadMatches = BuddyMatch::where('semester_id', $last->semester_id)
            ->where('status', 'active')
            ->whereHas('participants', fn($q) => $q
                ->where('buddy_match_participants.participant_id', $last->id)
                ->where('buddy_match_participants.role', 'mentor'))
            ->exists();

        if ($hadMatches) {
            return response()->json(['success' => true, 'data' => [
                'state'         => 'mentor_continuation_choices',
                'participant'   => $this->participantSummary($last),
                'semester'      => $last->semester?->toSettingsArray(),
                'next_semester' => $active?->toSettingsArray(),
                'pending_count' => 0,
            ]]);
        }

        // No matches in old semester → treat as new user
        return response()->json(['success' => true, 'data' => [
            'state'       => 'new_user',
            'participant' => $this->participantSummary($last),
        ]]);
    }

    /**
     * Return all semesters the authenticated user has participated in.
     * GET /api/buddy/semesters
     */
    public function getSemesters(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $semesters = BuddyParticipant::where('user_id', $userId)
            ->whereNotNull('semester_id')
            ->with('semester')
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn($p) => $p->semester)
            ->map(fn($p) => array_merge(
                $p->semester->toSettingsArray(),
                [
                    'role'               => $p->role,
                    'participant_id'     => $p->id,
                    'participant_status' => $p->status,
                ]
            ))
            ->unique('id')
            ->values();

        return response()->json(['success' => true, 'data' => $semesters]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Determine the state for a participant in the current (active) semester.
     * Handles pending_review, pending_match, and dashboard states.
     */
    private function resolveCurrentParticipantState(BuddyParticipant $participant, ?BuddySemesterSetting $activeSemester): JsonResponse
    {
        if ($participant->status === 'pending') {
            return response()->json(['success' => true, 'data' => [
                'state'              => 'pending_review',
                'participant'        => $this->participantSummary($participant),
                'semester'           => $activeSemester?->toSettingsArray(),
                'has_multiple_roles' => $this->hasMultipleRoles($participant->user_id),
            ]]);
        }

        // For mentors already in the active semester: check if they still have
        // pending continuation requests from mentees (linked to an OLD participant).
        // This handles the case where mentor accepted one mentee's continuation
        // but other mentees haven't been responded to yet.
        if ($participant->role === 'mentor') {
            $allMentorParticipantIds = BuddyParticipant::where('user_id', $participant->user_id)
                ->pluck('id');

            $pendingContinuations = BuddyContinuation::whereIn('mentor_participant_id', $allMentorParticipantIds)
                ->where('mentee_choice', 'continue')
                ->where('mentor_choice', 'pending')
                ->when($activeSemester, fn($q) => $q->where('to_semester_id', $activeSemester->id))
                ->count();

            if ($pendingContinuations > 0) {
                // Find the old participant referenced in the continuations
                $oldParticipant = BuddyParticipant::where('user_id', $participant->user_id)
                    ->where('id', '!=', $participant->id)
                    ->whereNotNull('semester_id')
                    ->orderByDesc('created_at')
                    ->first();

                return response()->json(['success' => true, 'data' => [
                    'state'         => 'mentor_continuation_choices',
                    'participant'   => $this->participantSummary($oldParticipant ?? $participant),
                    'semester'      => ($oldParticipant?->semester ?? $activeSemester)?->toSettingsArray(),
                    'next_semester' => $activeSemester?->toSettingsArray(),
                    'pending_count' => $pendingContinuations,
                ]]);
            }
        }

        $matchQuery = BuddyMatch::where('status', 'active')
            ->whereHas('participants', function ($q) use ($participant) {
                $q->where('buddy_match_participants.participant_id', $participant->id);
            });

        if ($activeSemester) {
            $matchQuery->where('semester_id', $activeSemester->id);
        }

        $hasMatch = $matchQuery->exists();
        $state = $hasMatch ? 'dashboard' : 'pending_match';

        return response()->json(['success' => true, 'data' => [
            'state'              => $state,
            'participant'        => $this->participantSummary($participant),
            'semester'           => $activeSemester?->toSettingsArray(),
            'has_multiple_roles' => $this->hasMultipleRoles($participant->user_id),
        ]]);
    }

    private function participantSummary(BuddyParticipant $p): array
    {
        return [
            'id'         => $p->id,
            'full_name'  => $p->full_name,
            'student_id' => $p->student_id,
            'role'       => $p->role,
            'status'     => $p->status,
        ];
    }

    /**
     * Check if the user has participated in multiple roles (both mentor and mentee)
     * across different semesters.
     */
    private function hasMultipleRoles(int $userId): bool
    {
        $roles = BuddyParticipant::where('user_id', $userId)
            ->whereNotNull('semester_id')
            ->distinct()
            ->pluck('role');

        return $roles->count() > 1;
    }
}
