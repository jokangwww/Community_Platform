<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Models\BuddyParticipant;
use App\Models\BuddyMatch;
use App\Models\BuddySession;
use App\Models\BuddySetting;
use App\Models\BuddyEvaluation;
use App\Models\BuddyTestimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    /**
     * Get analytics overview data
     */
    public function getAnalytics(): JsonResponse
    {
        $totalMentors = BuddyParticipant::mentors()->count();
        $activeMentors = BuddyParticipant::mentors()->where('status', 'active')->count();
        $pendingMentors = BuddyParticipant::mentors()->where('status', 'pending')->count();

        $totalMentees = BuddyParticipant::mentees()->count();
        $matchedMentees = BuddyParticipant::mentees()
            ->whereHas('menteeMatches', function ($query) {
                $query->where('status', 'active');
            })->count();
        $waitingMentees = $totalMentees - $matchedMentees;

        $totalRepeaters = BuddyParticipant::mentees()->where('is_repeater', true)->count();
        $matchedRepeaters = BuddyParticipant::mentees()
            ->where('is_repeater', true)
            ->whereHas('menteeMatches', function ($query) {
                $query->where('status', 'active');
            })->count();
        $waitingRepeaters = $totalRepeaters - $matchedRepeaters;

        $matchRate = $totalMentees > 0 ? round(($matchedMentees / $totalMentees) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'mentors' => [
                    'total' => $totalMentors,
                    'active' => $activeMentors,
                    'pending' => $pendingMentors,
                ],
                'mentees' => [
                    'total' => $totalMentees,
                    'matched' => $matchedMentees,
                    'waiting' => $waitingMentees,
                ],
                'repeaters' => [
                    'total' => $totalRepeaters,
                    'matched' => $matchedRepeaters,
                    'waiting' => $waitingRepeaters,
                ],
                'match_rate' => $matchRate,
            ]
        ]);
    }

    /**
     * Get pending mentor verifications
     */
    public function getPendingMentors(): JsonResponse
    {
        $pendingMentors = BuddyParticipant::mentors()
            ->where('status', 'pending')
            ->with('subjects')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($mentor) {
                return [
                    'id' => (string) $mentor->id,
                    'fullName' => $mentor->full_name,
                    'studentId' => $mentor->student_id,
                    'faculty' => $mentor->faculty,
                    'course' => $mentor->course,
                    'yearOfStudy' => $mentor->year_of_study,
                    'cgpa' => $mentor->cgpa,
                    'subjects' => $mentor->subjects->pluck('name')->toArray(),
                    'documentName' => $mentor->document_name,
                    'documentPath' => $mentor->document_path,
                    'registeredDate' => $mentor->created_at->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pendingMentors
        ]);
    }

    /**
     * Approve a mentor
     */
    public function approveMentor(Request $request, $id): JsonResponse
    {
        $mentor = BuddyParticipant::mentors()->where('status', 'pending')->findOrFail($id);
        
        $mentor->update([
            'status' => 'active',
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mentor approved successfully',
            'data' => [
                'id' => $mentor->id,
                'status' => $mentor->status,
            ]
        ]);
    }

    /**
     * Reject a mentor
     */
    public function rejectMentor(Request $request, $id): JsonResponse
    {
        $mentor = BuddyParticipant::mentors()->where('status', 'pending')->findOrFail($id);
        
        // Delete the uploaded document
        if ($mentor->document_path) {
            Storage::disk('public')->delete($mentor->document_path);
        }

        $mentor->update([
            'status' => 'rejected',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mentor rejected',
            'data' => [
                'id' => $mentor->id,
                'status' => $mentor->status,
            ]
        ]);
    }

    /**
     * Get check-in records
     */
    public function getCheckInRecords(): JsonResponse
    {
        $sessions = BuddySession::with(['match.mentor', 'match.mentee', 'match.subject'])
            ->orderBy('session_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($session) {
                $mentorCheckedIn = !is_null($session->mentor_check_in);
                $menteeCheckedIn = !is_null($session->mentee_check_in);
                
                $status = 'absent';
                if ($mentorCheckedIn && $menteeCheckedIn) {
                    $status = 'present';
                } elseif ($mentorCheckedIn || $menteeCheckedIn) {
                    $status = 'partial';
                }

                return [
                    'id' => (string) $session->id,
                    'sessionDate' => Carbon::parse($session->session_date)->format('Y-m-d'),
                    'sessionTopic' => $session->topic ?? 'No topic specified',
                    'mentorName' => $session->match->mentor->full_name ?? 'Unknown',
                    'menteeName' => $session->match->mentee->full_name ?? 'Unknown',
                    'mentorCheckInTime' => $session->mentor_check_in 
                        ? Carbon::parse($session->mentor_check_in)->format('Y-m-d H:i') 
                        : '',
                    'menteeCheckInTime' => $session->mentee_check_in 
                        ? Carbon::parse($session->mentee_check_in)->format('Y-m-d H:i') 
                        : '',
                    'groupSubject' => $session->match->subject->name ?? 'General',
                    'status' => $status,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Download mentor document with path traversal protection
     */
    public function downloadDocument($id): mixed
    {
        $mentor = BuddyParticipant::findOrFail($id);
        
        if (!$mentor->document_path) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        // Sanitize and validate the document path to prevent path traversal
        $documentPath = $mentor->document_path;
        
        // Check for path traversal attempts
        if (preg_match('/\\.\\.[\\/\\\\]|[\\/\\\\]\\.\\./', $documentPath) || 
            str_contains($documentPath, '..') ||
            str_contains($documentPath, "\0")) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid document path'
            ], 400);
        }

        // Ensure the path is within the allowed directory
        $fullPath = Storage::disk('public')->path($documentPath);
        $realPath = realpath($fullPath);
        $allowedBasePath = realpath(Storage::disk('public')->path('buddy-documents'));

        // Check if file exists and is within allowed directory
        if (!$realPath || !$allowedBasePath || !str_starts_with($realPath, $allowedBasePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found or access denied'
            ], 404);
        }

        if (!Storage::disk('public')->exists($documentPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found'
            ], 404);
        }

        // Sanitize the download filename
        $downloadName = $mentor->document_name ?? 'document';
        $downloadName = basename($downloadName); // Remove any path components
        $downloadName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $downloadName);

        return response()->download($fullPath, $downloadName);
    }

    /**
     * Get waiting list of unmatched mentees
     */
    public function getWaitingList(): JsonResponse
    {
        $waitingMentees = BuddyParticipant::mentees()
            ->where('status', 'active')
            ->whereDoesntHave('menteeMatches', function ($query) {
                $query->where('status', 'active');
            })
            ->with('subject')
            ->orderByRaw("CASE 
                WHEN priority_tier = 'high' THEN 1 
                WHEN priority_tier = 'normal' THEN 2 
                WHEN priority_tier = 'low' THEN 3 
                ELSE 4 
            END")
            ->orderBy('created_at', 'asc')
            ->get();

        $position = 1;
        $waitingList = $waitingMentees->map(function ($mentee) use (&$position) {
            return [
                'id' => (string) $mentee->id,
                'name' => $mentee->full_name,
                'studentId' => $mentee->student_id,
                'faculty' => $mentee->faculty,
                'course' => $mentee->course,
                'cgpa' => $mentee->cgpa,
                'subject' => $mentee->subject->name ?? 'Not specified',
                'registeredDate' => $mentee->created_at->format('Y-m-d'),
                'position' => $position++,
                'isRepeater' => $mentee->is_repeater,
                'rating' => $mentee->rating ?? 3.0,
                'priorityTier' => $mentee->priority_tier ?? 'normal',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $waitingList
        ]);
    }

    /**
     * Get all buddy programme settings
     */
    public function getSettings(): JsonResponse
    {
        $settings = BuddySetting::getAllSettings();

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update a specific setting
     */
    public function updateSetting(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required',
        ]);

        $key = $request->input('key');
        $value = $request->input('value');

        // Convert string 'true'/'false' to boolean for boolean settings
        if ($value === 'true') {
            $value = true;
        } elseif ($value === 'false') {
            $value = false;
        }

        $success = BuddySetting::setValue($key, $value);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
            'data' => [
                'key' => $key,
                'value' => $value
            ]
        ]);
    }

    /**
     * Get comprehensive report data for PDF generation
     */
    public function getReportData(): JsonResponse
    {
        $totalMentors = BuddyParticipant::mentors()->where('status', 'active')->count();
        $totalMentees = BuddyParticipant::mentees()->where('status', 'active')->count();
        $totalRepeaters = BuddyParticipant::mentees()->where('is_repeater', true)->count();
        
        $activeMatches = BuddyMatch::where('status', 'active')->count();
        $matchedMentees = BuddyParticipant::mentees()
            ->whereHas('menteeMatches', function ($query) {
                $query->where('status', 'active');
            })->count();
        $matchedRepeaters = BuddyParticipant::mentees()
            ->where('is_repeater', true)
            ->whereHas('menteeMatches', function ($query) {
                $query->where('status', 'active');
            })->count();

        $totalMentorApplications = BuddyParticipant::mentors()->count();
        $totalMenteeApplications = BuddyParticipant::mentees()->count();

        $totalSessions = BuddySession::count();
        $sessionsWithBothCheckedIn = BuddySession::whereNotNull('mentor_check_in')
            ->whereNotNull('mentee_check_in')
            ->count();
        $averageAttendanceRate = $totalSessions > 0 
            ? round(($sessionsWithBothCheckedIn / $totalSessions) * 100, 1) 
            : 0;

        $feedbackStats = BuddyEvaluation::selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_responses')
            ->first();
        $averageFeedbackRating = $feedbackStats->avg_rating 
            ? round($feedbackStats->avg_rating, 1) 
            : 0;
        $totalFeedbackResponses = $feedbackStats->total_responses ?? 0;

        $totalTestimonialsAwarded = BuddyTestimonial::where('status', 'approved')->count();
        $totalTestimonialsNotEligible = BuddyTestimonial::where('status', 'rejected')->count();
        
        // If no testimonials data exists yet, calculate from mentor performance
        if ($totalTestimonialsAwarded == 0 && $totalTestimonialsNotEligible == 0) {
            // Mentors with attendance >= 80% are eligible
            $eligibleMentors = 0;
            $notEligibleMentors = 0;
            
            $mentorsWithSessions = BuddyParticipant::mentors()
                ->where('status', 'active')
                ->withCount(['mentorMatches as total_sessions' => function ($query) {
                    $query->join('buddy_sessions', 'buddy_matches.id', '=', 'buddy_sessions.match_id');
                }])
                ->withCount(['mentorMatches as attended_sessions' => function ($query) {
                    $query->join('buddy_sessions', 'buddy_matches.id', '=', 'buddy_sessions.match_id')
                        ->whereNotNull('buddy_sessions.mentor_check_in');
                }])
                ->get();
            
            foreach ($mentorsWithSessions as $mentor) {
                if ($mentor->total_sessions > 0) {
                    $attendanceRate = ($mentor->attended_sessions / $mentor->total_sessions) * 100;
                    if ($attendanceRate >= 80) {
                        $eligibleMentors++;
                    } else {
                        $notEligibleMentors++;
                    }
                }
            }
            
            $totalTestimonialsAwarded = $eligibleMentors;
            $totalTestimonialsNotEligible = $notEligibleMentors;
        }

        $gapEligibleCount = 0;
        $gapNotEligibleCount = 0;
        
        // Check all participants (both mentors and mentees)
        $allParticipants = BuddyParticipant::where('status', 'active')
            ->get();
        
        foreach ($allParticipants as $participant) {
            $participantSessions = BuddySession::whereHas('match', function ($query) use ($participant) {
                $query->where('mentor_id', $participant->id)
                    ->orWhere('mentee_id', $participant->id);
            })->count();
            
            if ($participantSessions > 0) {
                $attendedSessions = BuddySession::whereHas('match', function ($query) use ($participant) {
                    $query->where('mentor_id', $participant->id)
                        ->orWhere('mentee_id', $participant->id);
                })->where(function ($query) use ($participant) {
                    $query->whereNotNull('mentor_check_in')
                        ->orWhereNotNull('mentee_check_in');
                })->count();
                
                $attendanceRate = ($attendedSessions / $participantSessions) * 100;
                
                if ($attendanceRate >= 80) {
                    $gapEligibleCount++;
                } else {
                    $gapNotEligibleCount++;
                }
            }
        }

        // Calculate summary statistics
        $matchSuccessRate = $totalMentees > 0 
            ? round(($matchedMentees / $totalMentees) * 100, 1) 
            : 0;
        $repeaterMatchRate = $totalRepeaters > 0 
            ? round(($matchedRepeaters / $totalRepeaters) * 100, 1) 
            : 0;
        $totalParticipants = $totalMentors + $totalMentees;
        $gapEligibilityRate = $totalParticipants > 0 
            ? round(($gapEligibleCount / $totalParticipants) * 100, 1) 
            : 0;

        // Get current semester/year
        $currentYear = date('Y');
        $currentMonth = date('n');
        $semester = $currentMonth >= 1 && $currentMonth <= 6 ? 1 : 2;
        $academicYear = $semester == 1 
            ? ($currentYear - 1) . '/' . $currentYear 
            : $currentYear . '/' . ($currentYear + 1);

        return response()->json([
            'success' => true,
            'data' => [
                'semester' => "Semester {$semester}",
                'academic_year' => $academicYear,
                'programme_overview' => [
                    'total_mentors' => $totalMentors,
                    'total_mentees' => $totalMentees,
                    'total_repeaters' => $totalRepeaters,
                    'total_matches' => $activeMatches,
                    'total_mentor_applications' => $totalMentorApplications,
                    'total_mentee_applications' => $totalMenteeApplications,
                ],
                'performance_metrics' => [
                    'average_attendance_rate' => $averageAttendanceRate,
                    'average_feedback_rating' => $averageFeedbackRating,
                    'total_feedback_responses' => $totalFeedbackResponses,
                ],
                'programme_recognition' => [
                    'total_testimonials_awarded' => $totalTestimonialsAwarded,
                    'not_eligible_testimonials' => $totalTestimonialsNotEligible,
                    'gap_eligible_count' => $gapEligibleCount,
                    'gap_not_eligible_count' => $gapNotEligibleCount,
                ],
                'report_summary' => [
                    'match_success_rate' => $matchSuccessRate,
                    'matched_mentees' => $matchedMentees,
                    'total_mentees' => $totalMentees,
                    'repeater_match_rate' => $repeaterMatchRate,
                    'matched_repeaters' => $matchedRepeaters,
                    'total_repeaters' => $totalRepeaters,
                    'total_participants' => $totalParticipants,
                    'gap_eligibility_rate' => $gapEligibilityRate,
                    'gap_eligible_count' => $gapEligibleCount,
                ],
            ]
        ]);
    }
}
