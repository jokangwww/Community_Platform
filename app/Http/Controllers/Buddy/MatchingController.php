<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Models\BuddyMatch;
use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use App\Models\BuddySemesterSetting;
use App\Services\BuddyMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchingController extends Controller
{
    protected BuddyMatchingService $matchingService;

    public function __construct(BuddyMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Get all active matches
     */
    public function getMatches(Request $request): JsonResponse
    {
        // Allow admin to filter by semester_id; default to active semester
        $semesterId = $request->query('semester_id');
        if ($semesterId) {
            $targetSemesterId = (int) $semesterId;
        } else {
            $activeSemester = BuddySemesterSetting::getActiveSemester();
            $targetSemesterId = $activeSemester?->id;
        }

        $query = BuddyMatch::with(['mentor', 'mentee', 'subject'])
            ->orderBy('matched_date', 'desc');

        if ($targetSemesterId) {
            $query->where('semester_id', $targetSemesterId);
        }

        $matches = $query->get()
            ->map(function ($match) {
                return [
                    'id' => (string) $match->id,
                    'mentor' => [
                        'id' => $match->mentor->id,
                        'name' => $match->mentor->full_name,
                        'studentId' => $match->mentor->student_id,
                        'faculty' => $match->mentor->faculty,
                        'cgpa' => $match->mentor->cgpa,
                    ],
                    'mentee' => [
                        'id' => $match->mentee->id,
                        'name' => $match->mentee->full_name,
                        'studentId' => $match->mentee->student_id,
                        'faculty' => $match->mentee->faculty,
                        'cgpa' => $match->mentee->cgpa,
                        'isRepeater' => $match->mentee->is_repeater,
                    ],
                    'subject' => $match->subject->name,
                    'matchedDate' => $match->matched_date->format('Y-m-d'),
                    'status' => $match->status,
                    'sessions' => $match->completed_sessions,
                    'totalSessions' => $match->total_sessions,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $matches
        ]);
    }

    /**
     * Get matching statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        $semesterId = $request->query('semester_id') ? (int) $request->query('semester_id') : null;
        $stats = $this->matchingService->getMatchingStats($semesterId);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Preview auto-match results without creating matches
     */
    public function previewAutoMatch(): JsonResponse
    {
        $preview = $this->matchingService->previewAutoMatch();

        return response()->json([
            'success' => true,
            'data' => $preview
        ]);
    }

    /**
     * Run the auto-matching algorithm
     */
    public function runAutoMatch(): JsonResponse
    {
        $results = $this->matchingService->runAutoMatch();

        return response()->json([
            'success' => empty($results['errors']),
            'data' => $results,
            'message' => empty($results['errors']) 
                ? "Successfully created {$results['matches_created']} matches"
                : 'Matching completed with errors'
        ]);
    }

    /**
     * Create a manual match
     */
    public function createManualMatch(Request $request): JsonResponse
    {
        $request->validate([
            'mentee_id' => 'required|integer|exists:buddy_participants,id',
            'mentor_id' => 'required|integer|exists:buddy_participants,id',
            'subject_id' => 'required|integer|exists:buddy_subjects,id',
        ]);

        $match = $this->matchingService->createManualMatch(
            $request->input('mentee_id'),
            $request->input('mentor_id'),
            $request->input('subject_id')
        );

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create match. Mentor may be at capacity or mentee already matched.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Match created successfully',
            'data' => [
                'id' => $match->id,
            ]
        ]);
    }

    /**
     * Cancel/unmatch a match
     */
    public function cancelMatch($id): JsonResponse
    {
        $match = BuddyMatch::findOrFail($id);
        
        $match->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Match cancelled successfully'
        ]);
    }

    /**
     * Get available mentors for manual matching
     */
    public function getAvailableMentors(Request $request): JsonResponse
    {
        $maxPerMentor = 3;
        $semesterId = $request->query('semester_id');
        if ($semesterId) {
            $targetSemesterId = (int) $semesterId;
        } else {
            $activeSemester = BuddySemesterSetting::getActiveSemester();
            $targetSemesterId = $activeSemester?->id;
        }

        $mentors = BuddyParticipant::mentors()
            ->where('status', 'active')
            ->when($targetSemesterId, fn($q) => $q->where('semester_id', $targetSemesterId))
            ->withCount(['mentorMatches' => function ($query) {
                $query->where('status', 'active');
            }])
            ->with('subjects')
            ->get()
            ->filter(function ($mentor) use ($maxPerMentor) {
                return $mentor->mentor_matches_count < $maxPerMentor;
            })
            ->map(function ($mentor) use ($maxPerMentor) {
                return [
                    'id' => $mentor->id,
                    'name' => $mentor->full_name,
                    'studentId' => $mentor->student_id,
                    'faculty' => $mentor->faculty,
                    'subjects' => $mentor->subjects->map(fn($s) => ['id' => $s->id, 'name' => $s->name]),
                    'currentMentees' => $mentor->mentor_matches_count,
                    'availableSlots' => $maxPerMentor - $mentor->mentor_matches_count,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $mentors
        ]);
    }

    /**
     * Get unmatched mentees for manual matching
     */
    public function getUnmatchedMentees(Request $request): JsonResponse
    {
        $semesterId = $request->query('semester_id');
        if ($semesterId) {
            $targetSemesterId = (int) $semesterId;
        } else {
            $activeSemester = BuddySemesterSetting::getActiveSemester();
            $targetSemesterId = $activeSemester?->id;
        }

        $mentees = BuddyParticipant::mentees()
            ->where('status', 'active')
            ->when($targetSemesterId, fn($q) => $q->where('semester_id', $targetSemesterId))
            ->whereDoesntHave('menteeMatches', function ($query) {
                $query->where('status', 'active');
            })
            ->with('subjects')
            ->orderByRaw("CASE 
                WHEN priority_tier = 'high' THEN 1 
                WHEN priority_tier = 'normal' THEN 2 
                WHEN priority_tier = 'low' THEN 3 
                ELSE 4 
            END")
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($mentee) {
                return [
                    'id' => $mentee->id,
                    'name' => $mentee->full_name,
                    'studentId' => $mentee->student_id,
                    'faculty' => $mentee->faculty,
                    'priorityTier' => $mentee->priority_tier,
                    'isRepeater' => $mentee->is_repeater,
                    'subjects' => $mentee->subjects->map(fn($s) => ['id' => $s->id, 'name' => $s->name]),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $mentees
        ]);
    }

    /**
     * Get all subjects
     */
    public function getSubjects(): JsonResponse
    {
        $subjects = BuddySubject::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }
}
