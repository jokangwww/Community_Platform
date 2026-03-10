<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Models\BuddyEvaluation;
use App\Models\BuddyMatch;
use App\Models\BuddyParticipant;
use App\Models\BuddySemesterSetting;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    /**
     * Get all evaluations for admin view
     */
    public function index(Request $request)
    {
        try {
            // Resolve target semester
            $semesterId = $request->query('semester_id');
            $targetSemester = $semesterId
                ? BuddySemesterSetting::find($semesterId)
                : BuddySemesterSetting::getActiveSemester();

            $query = BuddyEvaluation::with([
                'fromParticipant.user',
                'toParticipant.user',
                'match.subject'
            ]);

            // Scope to semester via the match relationship
            if ($targetSemester) {
                $query->whereHas('match', function ($q) use ($targetSemester) {
                    $q->where('semester_id', $targetSemester->id);
                });
            }

            $evaluations = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($evaluation) {
                return [
                    'id' => (string) $evaluation->id,
                    'fromName' => $evaluation->fromParticipant->full_name ?? ($evaluation->fromParticipant->user->name ?? 'Unknown'),
                    'fromId' => $evaluation->fromParticipant->student_id ?? ($evaluation->fromParticipant->user->student_id ?? 'N/A'),
                    'fromRole' => $evaluation->from_role,
                    'toName' => $evaluation->toParticipant->full_name ?? ($evaluation->toParticipant->user->name ?? 'Unknown'),
                    'toId' => $evaluation->toParticipant->student_id ?? ($evaluation->toParticipant->user->student_id ?? 'N/A'),
                    'toRole' => $evaluation->to_role,
                    'rating' => $evaluation->rating,
                    'feedback' => $evaluation->feedback,
                    'submittedDate' => $evaluation->created_at->format('Y-m-d'),
                ];
            });

            // Calculate statistics
            $totalSubmissions = $evaluations->count();
            $avgRating = $totalSubmissions > 0 
                ? round($evaluations->avg('rating'), 2) 
                : 0;
            $mentorFeedback = $evaluations->where('toRole', 'mentor')->count();
            $menteeFeedback = $evaluations->where('toRole', 'mentee')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'evaluations' => $evaluations->values(),
                    'stats' => [
                        'totalSubmissions' => $totalSubmissions,
                        'avgRating' => $avgRating,
                        'mentorFeedback' => $mentorFeedback,
                        'menteeFeedback' => $menteeFeedback,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch evaluations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit an evaluation
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
            'pair_student_id' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'required|string|min:10',
        ]);

        try {
            // Find participant directly by student_id in buddy_participants table
            // (participants may not always be linked to user accounts via user_id)
            $fromParticipant = BuddyParticipant::where('student_id', $request->student_id)
                ->whereIn('status', ['active', 'matched'])
                ->first();

            if (!$fromParticipant) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active buddy participation found for this student ID'
                ], 404);
            }

            // Find the pair's participant directly by student_id
            $toParticipant = BuddyParticipant::where('student_id', $request->pair_student_id)
                ->whereIn('status', ['active', 'matched'])
                ->first();

            if (!$toParticipant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pair buddy participation not found'
                ], 404);
            }

            // Find the match between these two participants
            $match = BuddyMatch::where(function ($query) use ($fromParticipant, $toParticipant) {
                $query->where('mentor_id', $fromParticipant->id)
                      ->where('mentee_id', $toParticipant->id);
            })->orWhere(function ($query) use ($fromParticipant, $toParticipant) {
                $query->where('mentor_id', $toParticipant->id)
                      ->where('mentee_id', $fromParticipant->id);
            })->first();

            if (!$match) {
                return response()->json([
                    'success' => false,
                    'message' => 'No match found between these participants'
                ], 404);
            }

            // Determine roles
            $fromRole = $match->mentor_id === $fromParticipant->id ? 'mentor' : 'mentee';
            $toRole = $fromRole === 'mentor' ? 'mentee' : 'mentor';

            // Check if evaluation already exists
            $existingEvaluation = BuddyEvaluation::where('match_id', $match->id)
                ->where('from_participant_id', $fromParticipant->id)
                ->where('to_participant_id', $toParticipant->id)
                ->first();

            if ($existingEvaluation) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already submitted an evaluation for this pair'
                ], 400);
            }

            // Create the evaluation
            $evaluation = BuddyEvaluation::create([
                'match_id' => $match->id,
                'from_participant_id' => $fromParticipant->id,
                'to_participant_id' => $toParticipant->id,
                'from_role' => $fromRole,
                'to_role' => $toRole,
                'rating' => $request->rating,
                'feedback' => $request->feedback,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Evaluation submitted successfully',
                'data' => $evaluation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit evaluation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user has already submitted evaluation for their pair
     */
    public function checkSubmission(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
            'pair_student_id' => 'required|string',
        ]);

        try {
            // Find participant directly by student_id in buddy_participants table
            $fromParticipant = BuddyParticipant::where('student_id', $request->student_id)
                ->whereIn('status', ['active', 'matched'])
                ->first();

            if (!$fromParticipant) {
                return response()->json([
                    'success' => true,
                    'data' => ['hasSubmitted' => false]
                ]);
            }

            // Find the pair's participant directly by student_id
            $toParticipant = BuddyParticipant::where('student_id', $request->pair_student_id)
                ->whereIn('status', ['active', 'matched'])
                ->first();

            if (!$toParticipant) {
                return response()->json([
                    'success' => true,
                    'data' => ['hasSubmitted' => false]
                ]);
            }

            // Find match
            $match = BuddyMatch::where(function ($query) use ($fromParticipant, $toParticipant) {
                $query->where('mentor_id', $fromParticipant->id)
                      ->where('mentee_id', $toParticipant->id);
            })->orWhere(function ($query) use ($fromParticipant, $toParticipant) {
                $query->where('mentor_id', $toParticipant->id)
                      ->where('mentee_id', $fromParticipant->id);
            })->first();

            if (!$match) {
                return response()->json([
                    'success' => true,
                    'data' => ['hasSubmitted' => false]
                ]);
            }

            $hasSubmitted = BuddyEvaluation::where('match_id', $match->id)
                ->where('from_participant_id', $fromParticipant->id)
                ->where('to_participant_id', $toParticipant->id)
                ->exists();

            return response()->json([
                'success' => true,
                'data' => ['hasSubmitted' => $hasSubmitted]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check submission status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export evaluations as CSV
     */
    public function export()
    {
        try {
            $evaluations = BuddyEvaluation::with([
                'fromParticipant.user',
                'toParticipant.user',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

            $csv = "ID,From Name,From Student ID,From Role,To Name,To Student ID,To Role,Rating,Feedback,Submitted Date\n";

            foreach ($evaluations as $evaluation) {
                $fromName = str_replace(',', ' ', $evaluation->fromParticipant->full_name ?? ($evaluation->fromParticipant->user->name ?? 'Unknown'));
                $toName = str_replace(',', ' ', $evaluation->toParticipant->full_name ?? ($evaluation->toParticipant->user->name ?? 'Unknown'));
                $feedback = str_replace([',', "\n", "\r"], [' ', ' ', ' '], $evaluation->feedback);
                
                $csv .= implode(',', [
                    $evaluation->id,
                    $fromName,
                    $evaluation->fromParticipant->student_id ?? ($evaluation->fromParticipant->user->student_id ?? 'N/A'),
                    $evaluation->from_role,
                    $toName,
                    $evaluation->toParticipant->student_id ?? ($evaluation->toParticipant->user->student_id ?? 'N/A'),
                    $evaluation->to_role,
                    $evaluation->rating,
                    '"' . $feedback . '"',
                    $evaluation->created_at->format('Y-m-d'),
                ]) . "\n";
            }

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="buddy_evaluations_' . date('Y-m-d') . '.csv"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export evaluations: ' . $e->getMessage()
            ], 500);
        }
    }
}
