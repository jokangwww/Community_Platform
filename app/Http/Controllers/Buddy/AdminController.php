<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Models\BuddyParticipant;
use App\Models\BuddyMatch;
use App\Models\BuddySession;
use App\Models\BuddySetting;
use App\Models\BuddySemesterSetting;
use App\Models\BuddyEvaluation;
use App\Models\BuddyTestimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Notifications\BuddyApprovalNotification;

class AdminController extends Controller
{
    /**
     * Get analytics overview data
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $semesterId = $request->query('semester_id');
        if (!$semesterId) {
            $active = BuddySemesterSetting::getActiveSemester();
            $semesterId = $active?->id;
        }

        $totalMentors = BuddyParticipant::mentors()
            ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
            ->count();
        $activeMentors = BuddyParticipant::mentors()
            ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
            ->where('status', 'active')->count();
        $pendingMentors = BuddyParticipant::mentors()
            ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
            ->where('status', 'pending')->count();

        $totalMentees = BuddyParticipant::mentees()
            ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
            ->count();
        $matchedMentees = BuddyParticipant::mentees()
            ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
            ->whereHas('menteeMatches', function ($query) use ($semesterId) {
                $query->where('status', 'active')
                      ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId));
            })->count();
        $waitingMentees = $totalMentees - $matchedMentees;

        $totalRepeaters = BuddyParticipant::mentees()
            ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
            ->where('is_repeater', true)->count();
        $matchedRepeaters = BuddyParticipant::mentees()
            ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
            ->where('is_repeater', true)
            ->whereHas('menteeMatches', function ($query) use ($semesterId) {
                $query->where('status', 'active')
                      ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId));
            })->count();
        $waitingRepeaters = $totalRepeaters - $matchedRepeaters;

        $matchRate = $totalMentees > 0 ? round(($matchedMentees / $totalMentees) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'mentors' => [
                    'total'   => $totalMentors,
                    'active'  => $activeMentors,
                    'pending' => $pendingMentors,
                ],
                'mentees' => [
                    'total'   => $totalMentees,
                    'matched' => $matchedMentees,
                    'waiting' => $waitingMentees,
                ],
                'repeaters' => [
                    'total'   => $totalRepeaters,
                    'matched' => $matchedRepeaters,
                    'waiting' => $waitingRepeaters,
                    'pending' => BuddyParticipant::mentees()
                        ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
                        ->where('is_repeater', true)
                        ->where('status', 'pending')
                        ->count(),
                ],
                'match_rate' => $matchRate,
            ]
        ]);
    }

    /**
     * Get pending mentor verifications (scoped by semester)
     */
    public function getPendingMentors(Request $request): JsonResponse
    {
        $semesterId = $request->query('semester_id');
        if (!$semesterId) {
            $active = BuddySemesterSetting::getActiveSemester();
            $semesterId = $active?->id;
        }

        $pendingMentors = BuddyParticipant::mentors()
            ->where('status', 'pending')
            ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
            ->with('subjects')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($mentor) {
                return [
                    'id'             => (string) $mentor->id,
                    'fullName'       => $mentor->full_name,
                    'studentId'      => $mentor->student_id,
                    'faculty'        => $mentor->faculty,
                    'course'         => $mentor->course,
                    'yearOfStudy'    => $mentor->year_of_study,
                    'cgpa'           => $mentor->cgpa,
                    'subjects'       => $mentor->subjects->pluck('name')->toArray(),
                    'documentName'   => $mentor->document_name,
                    'documentPath'   => $mentor->document_path,
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

        // Notify the mentor about approval
        if ($mentor->user) {
            $mentor->user->notify(new BuddyApprovalNotification('mentor', 'active'));
        }

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

        // Notify the mentor about rejection
        if ($mentor->user) {
            $mentor->user->notify(new BuddyApprovalNotification('mentor', 'rejected'));
        }

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
     * Get pending repeater verifications (scoped by semester)
     */
    public function getPendingRepeaters(Request $request): JsonResponse
    {
        $semesterId = $request->query('semester_id');
        if (!$semesterId) {
            $active = BuddySemesterSetting::getActiveSemester();
            $semesterId = $active?->id;
        }

        $pendingRepeaters = BuddyParticipant::mentees()
            ->where('status', 'pending')
            ->where('is_repeater', true)
            ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
            ->with('subject')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($repeater) {
                return [
                    'id'             => (string) $repeater->id,
                    'fullName'       => $repeater->full_name,
                    'studentId'      => $repeater->student_id,
                    'faculty'        => $repeater->faculty,
                    'course'         => $repeater->course,
                    'yearOfStudy'    => $repeater->year_of_study,
                    'cgpa'           => $repeater->cgpa,
                    'subject'        => $repeater->subject?->name ?? 'N/A',
                    'documentName'   => $repeater->document_name,
                    'documentPath'   => $repeater->document_path,
                    'registeredDate' => $repeater->created_at->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pendingRepeaters
        ]);
    }

    /**
     * Approve a repeater
     */
    public function approveRepeater(Request $request, $id): JsonResponse
    {
        $repeater = BuddyParticipant::mentees()
            ->where('is_repeater', true)
            ->where('status', 'pending')
            ->findOrFail($id);
        
        $repeater->update([
            'status' => 'active',
            'verified_at' => now(),
        ]);

        // Notify the mentee about approval
        if ($repeater->user) {
            $repeater->user->notify(new BuddyApprovalNotification('mentee', 'active'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Repeater approved successfully',
            'data' => [
                'id' => $repeater->id,
                'status' => $repeater->status,
            ]
        ]);
    }

    /**
     * Reject a repeater
     */
    public function rejectRepeater(Request $request, $id): JsonResponse
    {
        $repeater = BuddyParticipant::mentees()
            ->where('is_repeater', true)
            ->where('status', 'pending')
            ->findOrFail($id);
        
        // Delete the uploaded document
        if ($repeater->document_path) {
            Storage::disk('public')->delete($repeater->document_path);
        }

        $repeater->update([
            'status' => 'rejected',
        ]);

        // Notify the mentee about rejection
        if ($repeater->user) {
            $repeater->user->notify(new BuddyApprovalNotification('mentee', 'rejected'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Repeater rejected',
            'data' => [
                'id' => $repeater->id,
                'status' => $repeater->status,
            ]
        ]);
    }

    /**
     * Get check-in records (scoped by semester)
     */
    public function getCheckInRecords(Request $request): JsonResponse
    {
        $semesterId = $request->query('semester_id');
        if (!$semesterId) {
            $active = BuddySemesterSetting::getActiveSemester();
            $semesterId = $active?->id;
        }

        $sessions = BuddySession::with(['match.mentor', 'match.mentee', 'match.subject'])
            ->where('session_date', '<=', Carbon::today())
            ->when($semesterId, function ($q) use ($semesterId) {
                $q->whereHas('match', fn($m) => $m->where('semester_id', $semesterId));
            })
            ->orderBy('session_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($session) {
                $mentorCheckedIn = !is_null($session->mentor_check_in);

                // If mentor hasn't checked in on this session, check sibling sessions
                // (same date/time with same mentor across different matches)
                $mentorCheckInTime = $session->mentor_check_in;
                if (!$mentorCheckedIn && $session->match && $session->match->mentor) {
                    $mentorId = $session->match->mentor->id;
                    $siblingMentorMatchIds = \App\Models\BuddyMatch::whereHas('participants', function ($q) use ($mentorId) {
                        $q->where('buddy_match_participants.participant_id', $mentorId)
                          ->where('buddy_match_participants.role', 'mentor');
                    })->where('status', 'active')
                      ->where('semester_id', $session->match->semester_id)
                      ->pluck('id');

                    $siblingSession = BuddySession::whereIn('match_id', $siblingMentorMatchIds)
                        ->where('id', '!=', $session->id)
                        ->where('session_date', $session->session_date)
                        ->where('session_time', $session->session_time)
                        ->whereNotNull('mentor_check_in')
                        ->first();

                    if ($siblingSession) {
                        $mentorCheckedIn = true;
                        $mentorCheckInTime = $siblingSession->mentor_check_in;
                    }
                }

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
                    'mentorCheckInTime' => $mentorCheckInTime 
                        ? Carbon::parse($mentorCheckInTime)->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i') 
                        : null,
                    'menteeCheckInTime' => $session->mentee_check_in 
                        ? Carbon::parse($session->mentee_check_in)->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i') 
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
     * Get waiting list of unmatched mentees (scoped by semester)
     */
    public function getWaitingList(Request $request): JsonResponse
    {
        $semesterId = $request->query('semester_id');
        if (!$semesterId) {
            $active = BuddySemesterSetting::getActiveSemester();
            $semesterId = $active?->id;
        }

        $priorityEnabled = BuddySetting::isPriorityAllocationEnabled();

        $query = BuddyParticipant::mentees()
            ->where('status', 'active')
            ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
            ->whereDoesntHave('menteeMatches', function ($query) use ($semesterId) {
                $query->where('status', 'active')
                      ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId));
            })
            ->with('subject');

        if ($priorityEnabled) {
            // Sort by priority tier then registration date
            $query->orderByRaw("CASE 
                WHEN priority_tier = 'high' THEN 1 
                WHEN priority_tier = 'normal' THEN 2 
                WHEN priority_tier = 'low' THEN 3 
                ELSE 4 
            END")
            ->orderBy('created_at', 'asc');
        } else {
            // Simple first-come, first-served
            $query->orderBy('created_at', 'asc');
        }

        $waitingMentees = $query->get();

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
            'data' => $waitingList,
            'priorityEnabled' => $priorityEnabled,
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
     * Update the current active semester in-place (does NOT archive/replace it)
     */
    public function updateSemesterSetting(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year' => 'required|string',
            'semester'      => 'required|integer|in:1,2,3',
            'duration_type' => 'required|string|in:long,short',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
        ]);

        $semester = BuddySemesterSetting::getActiveSemester();

        if (!$semester) {
            return response()->json([
                'success' => false,
                'message' => 'No active semester found. Use Start New Semester instead.',
            ], 404);
        }

        $totalWeeks = $request->duration_type === 'long' ? 14 : 7;

        $semester->update([
            'academic_year' => $request->academic_year,
            'semester'      => $request->semester,
            'duration_type' => $request->duration_type,
            'total_weeks'   => $totalWeeks,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'updated_by'    => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Semester updated successfully',
            'data'    => $semester->fresh()->toSettingsArray(),
        ]);
    }

    /**
     * Get the active semester setting
     */
    public function getSemesterSetting(): JsonResponse
    {
        $semester = BuddySemesterSetting::getActiveSemester();

        return response()->json([
            'success' => true,
            'data' => $semester ? $semester->toSettingsArray() : null,
        ]);
    }

    /**
     * Get all semesters (active + archived) for filter dropdowns
     */
    public function getAllSemesters(): JsonResponse
    {
        $semesters = BuddySemesterSetting::getAllSemesters()
            ->map(fn($s) => $s->toSettingsArray());

        return response()->json(['success' => true, 'data' => $semesters]);
    }

    /**
     * Start a new semester (archives the current one by deactivating it)
     */
    public function saveSemesterSetting(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year' => 'required|string',
            'semester'      => 'required|integer|in:1,2,3',
            'duration_type' => 'required|string|in:long,short',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
        ]);

        $totalWeeks = $request->duration_type === 'long' ? 14 : 7;

        // Deactivate the current active semester (archive it — data rows remain untouched)
        BuddySemesterSetting::where('is_active', true)->update(['is_active' => false]);

        // Create the new active semester
        $semester = BuddySemesterSetting::create([
            'academic_year'       => $request->academic_year,
            'semester'            => $request->semester,
            'duration_type'       => $request->duration_type,
            'total_weeks'         => $totalWeeks,
            'start_date'          => $request->start_date,
            'end_date'            => $request->end_date,
            'is_active'           => true,
            'registration_open'   => true,
            'evaluation_enabled'  => false,
            'testimonial_enabled' => false,
            'priority_allocation' => true,
            'updated_by'          => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'New semester started successfully',
            'data'    => $semester->toSettingsArray(),
        ]);
    }

    /**
     * Get comprehensive report data for PDF generation (scoped by semester)
     */
    public function getReportData(Request $request): JsonResponse
    {
        // Resolve semester — defaults to active; report can pull any past semester
        $semesterId = $request->query('semester_id');
        $semester = $semesterId
            ? BuddySemesterSetting::find($semesterId)
            : BuddySemesterSetting::getActiveSemester();

        $semesterIdFilter = $semester?->id;

        $totalMentors = BuddyParticipant::mentors()
            ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter))
            ->where('status', 'active')->count();
        $totalMentees = BuddyParticipant::mentees()
            ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter))
            ->where('status', 'active')->count();
        $totalRepeaters = BuddyParticipant::mentees()
            ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter))
            ->where('is_repeater', true)->count();

        $activeMatches = BuddyMatch::where('status', 'active')
            ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter))
            ->count();
        $matchedMentees = BuddyParticipant::mentees()
            ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter))
            ->whereHas('menteeMatches', function ($query) use ($semesterIdFilter) {
                $query->where('status', 'active')
                      ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter));
            })->count();
        $matchedRepeaters = BuddyParticipant::mentees()
            ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter))
            ->where('is_repeater', true)
            ->whereHas('menteeMatches', function ($query) use ($semesterIdFilter) {
                $query->where('status', 'active')
                      ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter));
            })->count();

        $totalMentorApplications = BuddyParticipant::mentors()
            ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter))
            ->count();
        $totalMenteeApplications = BuddyParticipant::mentees()
            ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter))
            ->count();

        // Sessions scoped via match semester
        $sessionQuery = BuddySession::when($semesterIdFilter, function ($q) use ($semesterIdFilter) {
            $q->whereHas('match', fn($m) => $m->where('semester_id', $semesterIdFilter));
        });
        $totalSessions = $sessionQuery->count();
        $sessionsWithBothCheckedIn = (clone $sessionQuery)
            ->whereNotNull('mentor_check_in')->whereNotNull('mentee_check_in')->count();
        $averageAttendanceRate = $totalSessions > 0
            ? round(($sessionsWithBothCheckedIn / $totalSessions) * 100, 1)
            : 0;

        $feedbackStats = BuddyEvaluation::when($semesterIdFilter, function ($q) use ($semesterIdFilter) {
            $q->whereHas('match', fn($m) => $m->where('semester_id', $semesterIdFilter));
        })->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_responses')->first();
        $averageFeedbackRating   = $feedbackStats->avg_rating ? round($feedbackStats->avg_rating, 1) : 0;
        $totalFeedbackResponses  = $feedbackStats->total_responses ?? 0;

        $totalTestimonialsAwarded    = BuddyTestimonial::where('status', 'approved')
            ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter))
            ->count();
        $totalTestimonialsNotEligible = BuddyTestimonial::where('status', 'rejected')
            ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter))
            ->count();

        $gapEligibleCount    = 0;
        $gapNotEligibleCount = 0;

        $allParticipants = BuddyParticipant::where('status', 'active')
            ->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter))
            ->get();

        foreach ($allParticipants as $participant) {
            $participantSessions = BuddySession::whereHas('match', function ($query) use ($participant, $semesterIdFilter) {
                $query->where(function ($q) use ($participant) {
                    $q->where('mentor_id', $participant->id)->orWhere('mentee_id', $participant->id);
                })->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter));
            })->count();

            if ($participantSessions > 0) {
                $attendedSessions = BuddySession::whereHas('match', function ($query) use ($participant, $semesterIdFilter) {
                    $query->where(function ($q) use ($participant) {
                        $q->where('mentor_id', $participant->id)->orWhere('mentee_id', $participant->id);
                    })->when($semesterIdFilter, fn($q) => $q->where('semester_id', $semesterIdFilter));
                })->where(function ($query) {
                    $query->whereNotNull('mentor_check_in')->orWhereNotNull('mentee_check_in');
                })->count();

                if (($attendedSessions / $participantSessions) * 100 >= 80) {
                    $gapEligibleCount++;
                } else {
                    $gapNotEligibleCount++;
                }
            }
        }

        $matchSuccessRate    = $totalMentees > 0 ? round(($matchedMentees / $totalMentees) * 100, 1) : 0;
        $repeaterMatchRate   = $totalRepeaters > 0 ? round(($matchedRepeaters / $totalRepeaters) * 100, 1) : 0;
        $totalParticipants   = $totalMentors + $totalMentees;
        $gapEligibilityRate  = $totalParticipants > 0 ? round(($gapEligibleCount / $totalParticipants) * 100, 1) : 0;

        // Use the resolved semester for label, not system clock
        $semesterLabel  = $semester ? "Semester {$semester->semester}" : 'Unknown Semester';
        $academicYearLabel = $semester?->academic_year ?? 'Unknown';

        return response()->json([
            'success' => true,
            'data' => [
                'semester'            => $semesterLabel,
                'academic_year'       => $academicYearLabel,
                'programme_overview'  => [
                    'total_mentors'              => $totalMentors,
                    'total_mentees'              => $totalMentees,
                    'total_repeaters'            => $totalRepeaters,
                    'total_matches'              => $activeMatches,
                    'total_mentor_applications'  => $totalMentorApplications,
                    'total_mentee_applications'  => $totalMenteeApplications,
                ],
                'performance_metrics' => [
                    'average_attendance_rate' => $averageAttendanceRate,
                    'average_feedback_rating' => $averageFeedbackRating,
                    'total_feedback_responses' => $totalFeedbackResponses,
                ],
                'programme_recognition' => [
                    'total_testimonials_awarded'  => $totalTestimonialsAwarded,
                    'not_eligible_testimonials'   => $totalTestimonialsNotEligible,
                    'gap_eligible_count'          => $gapEligibleCount,
                    'gap_not_eligible_count'      => $gapNotEligibleCount,
                ],
                'report_summary' => [
                    'match_success_rate'   => $matchSuccessRate,
                    'matched_mentees'      => $matchedMentees,
                    'total_mentees'        => $totalMentees,
                    'repeater_match_rate'  => $repeaterMatchRate,
                    'matched_repeaters'    => $matchedRepeaters,
                    'total_repeaters'      => $totalRepeaters,
                    'total_participants'   => $totalParticipants,
                    'gap_eligibility_rate' => $gapEligibilityRate,
                    'gap_eligible_count'   => $gapEligibleCount,
                ],
            ]
        ]);
    }
}
