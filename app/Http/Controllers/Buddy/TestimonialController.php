<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Models\BuddyTestimonial;
use App\Models\BuddyParticipant;
use App\Models\BuddySemesterSetting;
use App\Models\User;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    /**
     * Get all testimonial requests for admin view
     */
    public function index(Request $request)
    {
        try {
            // Resolve target semester
            $semesterId = $request->query('semester_id');
            $targetSemester = $semesterId
                ? BuddySemesterSetting::find($semesterId)
                : BuddySemesterSetting::getActiveSemester();

            $query = BuddyTestimonial::with(['participant.user', 'participant.subject']);

            // Scope to semester via participant relationship
            if ($targetSemester) {
                $query->whereHas('participant', function ($q) use ($targetSemester) {
                    $q->where('semester_id', $targetSemester->id);
                });
            }

            $testimonials = $query->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($testimonial) {
                    return [
                        'id' => (string) $testimonial->id,
                        'mentorName' => $testimonial->participant->user->name ?? 'Unknown',
                        'mentorId' => $testimonial->participant->user->student_id ?? 'N/A',
                        'faculty' => $testimonial->participant->faculty ?? 'N/A',
                        'programme' => $testimonial->participant->course ?? 'N/A',
                        'totalSessions' => $testimonial->total_sessions,
                        'totalMentees' => $testimonial->total_mentees,
                        'skillsTaught' => $testimonial->skills_taught ?? [],
                        'avgFeedbackScore' => (float) $testimonial->avg_feedback_score,
                        'attendanceRate' => (float) $testimonial->attendance_rate,
                        'semesterYear' => $testimonial->semester_year,
                        'requestDate' => $testimonial->created_at->format('Y-m-d'),
                        'status' => $testimonial->status,
                    ];
                });

            $pendingCount = $testimonials->where('status', 'pending')->count();
            $approvedCount = $testimonials->where('status', 'approved')->count();
            $rejectedCount = $testimonials->where('status', 'rejected')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'testimonials' => $testimonials->values(),
                    'stats' => [
                        'pendingCount' => $pendingCount,
                        'approvedCount' => $approvedCount,
                        'rejectedCount' => $rejectedCount,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch testimonials: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if mentor has an approved testimonial
     */
    public function checkRequest(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
        ]);

        try {
            $user = User::where('student_id', $request->student_id)->first();
            if (!$user) {
                return response()->json([
                    'success' => true,
                    'data' => ['hasRequested' => false, 'status' => null]
                ]);
            }

            $participant = BuddyParticipant::where('user_id', $user->id)
                ->where('role', 'mentor')
                ->first();

            if (!$participant) {
                return response()->json([
                    'success' => true,
                    'data' => ['hasRequested' => false, 'status' => null]
                ]);
            }

            // Use the participant's semester to find testimonials dynamically
            $testimonial = BuddyTestimonial::where('participant_id', $participant->id)
                ->latest()
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'hasRequested' => $testimonial !== null,
                    'status' => $testimonial?->status,
                    'testimonial' => $testimonial ? [
                        'id' => (string) $testimonial->id,
                        'totalSessions' => $testimonial->total_sessions,
                        'totalMentees' => $testimonial->total_mentees,
                        'skillsTaught' => $testimonial->skills_taught ?? [],
                        'avgFeedbackScore' => (float) $testimonial->avg_feedback_score,
                        'attendanceRate' => (float) $testimonial->attendance_rate,
                        'semesterYear' => $testimonial->semester_year,
                        'status' => $testimonial->status,
                    ] : null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check request status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a testimonial request
     */
    public function approve(Request $request, $id)
    {
        try {
            $testimonial = BuddyTestimonial::findOrFail($id);

            if ($testimonial->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This testimonial has already been processed'
                ], 400);
            }

            $testimonial->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Testimonial approved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve testimonial: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a testimonial request
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $testimonial = BuddyTestimonial::findOrFail($id);

            if ($testimonial->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This testimonial has already been processed'
                ], 400);
            }

            $testimonial->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason,
                'rejected_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Testimonial rejected'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject testimonial: ' . $e->getMessage()
            ], 500);
        }
    }
}
