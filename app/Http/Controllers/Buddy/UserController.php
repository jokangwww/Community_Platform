<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Models\BuddyMatch;
use App\Models\BuddyParticipant;
use App\Models\BuddySchedule;
use App\Models\BuddySession;
use App\Models\BuddyTimeSlot;
use App\Models\BuddyTimeSlotVote;
use App\Models\BuddySemesterSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        set_time_limit(120);
    }

    /**
     * Get user dashboard data
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $studentId  = $request->query('student_id');
        $semesterId = $request->query('semester_id');

        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required'
            ], 400);
        }

        // Resolve the target semester (provided or active)
        $targetSemester = $semesterId
            ? BuddySemesterSetting::find($semesterId)
            : BuddySemesterSetting::getActiveSemester();

        // Get participant info — scoped to semester when available
        $participantQuery = BuddyParticipant::with('subject')
            ->where('student_id', $studentId);

        if ($targetSemester) {
            $participantQuery->where('semester_id', $targetSemester->id);
        }

        $participant = $participantQuery->first();

        // Fallback: if no record exists for the active/requested semester (e.g. new semester
        // just activated but this student registered in a previous semester), load their most
        // recent participant record so the dashboard still works (will be shown read-only).
        if (!$participant && !$semesterId) {
            $participant = BuddyParticipant::with('subject')
                ->where('student_id', $studentId)
                ->orderByDesc('created_at')
                ->first();
        }

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not found'
            ], 404);
        }

        // Determine read-only mode (viewing a non-active past semester)
        $activeSemester = BuddySemesterSetting::getActiveSemester();
        $isReadonly = $activeSemester && $participant->semester_id !== null
            && $participant->semester_id !== $activeSemester->id;

        // Get active match with partner info (scoped to participant's semester)
        $match = null;
        $partner = null;

        if ($participant->role === 'mentor') {
            // Get first active match via pivot table
            $matchRecord = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                    $query->where('buddy_match_participants.participant_id', $participant->id)
                          ->where('buddy_match_participants.role', 'mentor');
                })
                ->when($participant->semester_id, fn($q) => $q->where('semester_id', $participant->semester_id))
                ->with(['participants', 'subject'])
                ->where('status', 'active')
                ->first();
            
            if ($matchRecord) {
                // Get first mentee from the match
                $partner = $matchRecord->participants()
                    ->wherePivot('role', 'mentee')
                    ->first();
                $match = $matchRecord;
            }
        } else {
            // Get first active match via pivot table
            $matchRecord = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                    $query->where('buddy_match_participants.participant_id', $participant->id)
                          ->where('buddy_match_participants.role', 'mentee');
                })
                ->when($participant->semester_id, fn($q) => $q->where('semester_id', $participant->semester_id))
                ->with(['participants', 'subject'])
                ->where('status', 'active')
                ->first();
            
            if ($matchRecord) {
                // Get the mentor from the match
                $partner = $matchRecord->participants()
                    ->wherePivot('role', 'mentor')
                    ->first();
                $match = $matchRecord;
            }
        }

        // Get sessions if matched
        $sessions = [];
        $upcomingMeetings = [];
        $completedSessions = 0;
        $totalSessions = 0;
        $totalSessionsUpToToday = 0;
        $completedSessionsUpToToday = 0;

        if ($match) {
            $sessionRecords = BuddySession::where('match_id', $match->id)
                ->orderBy('session_date', 'desc')
                ->orderBy('session_time', 'desc')
                ->get();

            foreach ($sessionRecords as $session) {
                $status = 'pending';
                $attendanceSubmitted = false;

                if ($session->status === 'completed') {
                    $status = 'completed';
                    $attendanceSubmitted = $session->mentor_check_in && $session->mentee_check_in;
                    $completedSessions++;
                } elseif ($session->status === 'missed') {
                    $status = 'missed';
                }

                $sessions[] = [
                    'id' => (string)$session->id,
                    'date' => $session->session_date->format('Y-m-d'),
                    'time' => $session->session_time,
                    'topic' => $session->topic ?? 'Session',
                    'status' => $status,
                    'attendanceSubmitted' => $attendanceSubmitted,
                ];
            }

            // Use actual session count when stored total_sessions is 0 or null
            $totalSessions = ($match->total_sessions > 0) ? $match->total_sessions : count($sessionRecords);
            $completedSessions = ($match->completed_sessions > 0) ? $match->completed_sessions : $completedSessions;

            // For attendance rate: only count sessions that have occurred (up to today)
            $today = now()->toDateString();
            $sessionsUpToToday = $sessionRecords->filter(fn($s) => $s->session_date->format('Y-m-d') <= $today);
            $totalSessionsUpToToday = $sessionsUpToToday->count();
            $completedSessionsUpToToday = $sessionsUpToToday->where('status', 'completed')->count();
        }

        // Attendance rate: based only on sessions that have occurred up to today
        $attendanceRate = $totalSessionsUpToToday > 0 
            ? round(($completedSessionsUpToToday / $totalSessionsUpToToday) * 100) 
            : 0;

        // Get confirmed weekly schedule if exists
        $weeklySchedule = null;
        if ($match) {
            $confirmedSchedule = BuddySchedule::where('match_id', $match->id)
                ->where('status', 'confirmed')
                ->first();
            
            if ($confirmedSchedule) {
                $weeklySchedule = [
                    'day' => $confirmedSchedule->day,
                    'time' => $confirmedSchedule->formatted_time,
                    'startTime' => date('H:i', strtotime($confirmedSchedule->start_time)),
                    'endTime' => date('H:i', strtotime($confirmedSchedule->end_time)),
                ];
            }
        }

        // Build response
        $response = [
            'user' => [
                'id' => $participant->id,
                'name' => $participant->full_name,
                'studentId' => $participant->student_id,
                'role' => $participant->role,
                'registrationStatus' => $participant->status,
                'rating' => (float)$participant->rating,
                'faculty' => $participant->faculty,
                'course' => $participant->course,
                'subject' => $participant->subject ? [
                    'id' => $participant->subject->id,
                    'name' => $participant->subject->name,
                    'type' => $participant->subject->type,
                ] : null,
            ],
            'pairing' => $match ? [
                'id' => (string)$match->id,
                'partnerName' => $partner ? $partner->full_name : 'N/A',
                'partnerStudentId' => $partner ? $partner->student_id : 'N/A',
                'subject' => $match->subject ? $match->subject->name : ($participant->subject ? $participant->subject->name : 'N/A'),
                'matchedDate' => $match->matched_date->format('Y-m-d'),
                'progressPercentage' => $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0,
                'totalSessions' => $totalSessions,
                'completedSessions' => $completedSessions,
            ] : null,
            'meetings' => $sessions,
            'weeklySchedule' => $weeklySchedule,
            'stats' => [
                'attendanceRate' => $attendanceRate,
                'completedSessions' => $completedSessions,
                'totalSessions' => $totalSessions,
                'pendingTasks' => 0, // Can be calculated based on pending meetings
                'unreadNotifications' => 0, // Placeholder for future notifications system
            ],
            'is_readonly'  => $isReadonly,
            'semester_id'  => $participant->semester_id,
        ];

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Get user's sessions/meetings
     */
    public function getSessions(Request $request): JsonResponse
    {
        $studentId = $request->query('student_id');

        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required'
            ], 400);
        }

        // Use semester-scoped participant from middleware
        $participant = $request->attributes->get('participant');
        if (!$participant) {
            $semId = $request->attributes->get('semester_id');
            $query = BuddyParticipant::where('student_id', $studentId);
            if ($semId) {
                $query->where('semester_id', $semId);
            }
            $participant = $query->orderByDesc('created_at')->first();
        }

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not found'
            ], 404);
        }

        // Get match (scoped to participant's semester)
        $matchQuery = $participant->role === 'mentor' 
            ? BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
              })
            : BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentee');
              });

        // Scope to participant's semester
        if ($participant->semester_id) {
            $matchQuery->where('semester_id', $participant->semester_id);
        }

        $match = $matchQuery->where('status', 'active')->first();

        if (!$match) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // Get the mentor's participant ID for this match to find sibling sessions
        $mentorPivot = \DB::table('buddy_match_participants')
            ->where('match_id', $match->id)
            ->where('role', 'mentor')
            ->first();
        $mentorMatchIds = collect([$match->id]);
        if ($mentorPivot) {
            $mentorMatchIds = BuddyMatch::whereHas('participants', function ($q) use ($mentorPivot) {
                $q->where('buddy_match_participants.participant_id', $mentorPivot->participant_id)
                  ->where('buddy_match_participants.role', 'mentor');
            })->where('status', 'active')
              ->where('semester_id', $match->semester_id)
              ->pluck('id');
        }

        $sessions = BuddySession::where('match_id', $match->id)
            ->where('session_date', '<=', now()->toDateString())
            ->orderBy('session_date', 'desc')
            ->get()
            ->map(function ($session) use ($mentorMatchIds) {
                // Auto-mark sessions as missed if session time has passed and mentee hasn't checked in
                if ($session->status === 'pending' && !$session->mentee_check_in) {
                    $sessionDateStr = $session->session_date->format('Y-m-d');
                    $sessionEndTime = $session->session_end_time ?? '23:59:59';
                    $sessionDeadline = \Carbon\Carbon::parse($sessionDateStr . ' ' . $sessionEndTime, 'Asia/Kuala_Lumpur');
                    
                    if (now('Asia/Kuala_Lumpur')->gt($sessionDeadline)) {
                        $session->status = 'missed';
                        $session->save();
                    }
                }

                // If mentor hasn't checked in on this session, check sibling sessions
                $mentorCheckIn = $session->mentor_check_in;
                if (!$mentorCheckIn) {
                    $siblingSession = BuddySession::whereIn('match_id', $mentorMatchIds)
                        ->where('id', '!=', $session->id)
                        ->where('session_date', $session->session_date)
                        ->where('session_time', $session->session_time)
                        ->whereNotNull('mentor_check_in')
                        ->first();
                    if ($siblingSession) {
                        $mentorCheckIn = $siblingSession->mentor_check_in;
                    }
                }
                
                return [
                    'id' => (string)$session->id,
                    'date' => $session->session_date->format('Y-m-d'),
                    'time' => $session->session_time,
                    'endTime' => $session->session_end_time,
                    'topic' => $session->topic ?? 'Session',
                    'description' => $session->description,
                    'status' => $session->status,
                    'mentorCheckIn' => $mentorCheckIn ? \Carbon\Carbon::parse($mentorCheckIn)->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s') : null,
                    'menteeCheckIn' => $session->mentee_check_in ? $session->mentee_check_in->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s') : null,
                    'notes' => $session->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Submit attendance/check-in for a session
     */
    public function submitCheckIn(Request $request, $sessionId): JsonResponse
    {
        // Block writes on archived semesters
        if ($request->attributes->get('readonly')) {
            return response()->json(['success' => false, 'message' => 'This semester is archived and read-only'], 403);
        }

        $request->validate([
            'student_id' => 'required|string',
        ]);

        // Use semester-scoped participant from middleware
        $participant = $request->attributes->get('participant')
            ?? BuddyParticipant::where('student_id', $request->student_id)->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not found'
            ], 404);
        }

        $session = BuddySession::find($sessionId);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        // For mentees, validate check-in is within session time
        if ($participant->role === 'mentee') {
            // Check if session already completed or missed
            if ($session->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Session already completed'
                ], 400);
            }
            
            if ($session->status === 'missed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Session time has ended. You are marked as absent.'
                ], 400);
            }

            // Check if within session time window
            $sessionDateStr = $session->session_date->format('Y-m-d');
            $sessionEndTime = $session->session_end_time ?? '23:59:59';
            
            // Combine date and end time to create deadline (use local timezone since session times are stored in MYT)
            $sessionDeadline = \Carbon\Carbon::parse($sessionDateStr . ' ' . $sessionEndTime, 'Asia/Kuala_Lumpur');
            $now = now('Asia/Kuala_Lumpur');
            
            // If current time is past session end time, mark as missed/absent
            if ($now->gt($sessionDeadline)) {
                $session->status = 'missed';
                $session->save();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Session time has ended. You are marked as absent.'
                ], 400);
            }
        }

        // Update check-in based on role
        if ($participant->role === 'mentor') {
            $session->mentor_check_in = now();
        } else {
            $session->mentee_check_in = now();
        }

        // If both have checked in, mark session as completed
        if ($session->mentor_check_in && $session->mentee_check_in) {
            $session->status = 'completed';
            
            // Update match completed sessions count
            $match = $session->match;
            if ($match) {
                $match->completed_sessions = BuddySession::where('match_id', $match->id)
                    ->where('status', 'completed')
                    ->count();
                $match->save();
            }
        }

        $session->save();

        // Propagate mentor check-in to sibling sessions (same date/time across all mentor's matches)
        if ($participant->role === 'mentor') {
            $mentorMatchIds = BuddyMatch::whereHas('participants', function ($q) use ($participant) {
                $q->where('buddy_match_participants.participant_id', $participant->id)
                  ->where('buddy_match_participants.role', 'mentor');
            })
            ->where('status', 'active')
            ->when($participant->semester_id, fn($q) => $q->where('semester_id', $participant->semester_id))
            ->pluck('id');

            $siblingSessions = BuddySession::whereIn('match_id', $mentorMatchIds)
                ->where('id', '!=', $session->id)
                ->where('session_date', $session->session_date)
                ->where('session_time', $session->session_time)
                ->get();

            foreach ($siblingSessions as $siblingSession) {
                $siblingSession->mentor_check_in = $session->mentor_check_in;

                // If this sibling session's mentee also checked in, mark completed
                if ($siblingSession->mentor_check_in && $siblingSession->mentee_check_in) {
                    $siblingSession->status = 'completed';
                }
                $siblingSession->save();

                // Update sibling match completed sessions count
                $siblingMatch = $siblingSession->match;
                if ($siblingMatch) {
                    $siblingMatch->completed_sessions = BuddySession::where('match_id', $siblingMatch->id)
                        ->where('status', 'completed')
                        ->count();
                    $siblingMatch->save();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-in recorded successfully',
            'data' => [
                'sessionId' => $session->id,
                'status' => $session->status,
                'mentorCheckIn' => $session->mentor_check_in ? $session->mentor_check_in->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s') : null,
                'menteeCheckIn' => $session->mentee_check_in ? $session->mentee_check_in->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s') : null,
            ]
        ]);
    }

    /**
     * Get scheduling data for a match
     */
    public function getSchedule(Request $request): JsonResponse
    {
        $studentId = $request->query('student_id');

        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required'
            ], 400);
        }

        // Use semester-scoped participant from middleware
        $participant = $request->attributes->get('participant');
        if (!$participant) {
            $semId = $request->attributes->get('semester_id');
            $query = BuddyParticipant::where('student_id', $studentId);
            if ($semId) {
                $query->where('semester_id', $semId);
            }
            $participant = $query->orderByDesc('created_at')->first();
        }

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not found'
            ], 404);
        }

        // Get match(es): scoped to participant's semester
        $matchQuery = $participant->role === 'mentor'
            ? BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
              })
            : BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentee');
              });

        // Scope to participant's semester for correct semester data
        if ($participant->semester_id) {
            $matchQuery->where('semester_id', $participant->semester_id);
        }

        if ($participant->role === 'mentor') {
            $allMatches = $matchQuery->where('status', 'active')->get();
            $match = $allMatches->first();
        } else {
            $match = $matchQuery->where('status', 'active')->first();
            // For mentees, include all sibling matches (same mentor) so vote aggregation works
            if ($match) {
                $allMatches = BuddyMatch::where('mentor_id', $match->mentor_id)
                    ->where('status', 'active')
                    ->where('semester_id', $match->semester_id)
                    ->get();
            } else {
                $allMatches = collect();
            }
        }

        if (!$match) {
            return response()->json([
                'success' => true,
                'data' => [
                    'hasMatch' => false,
                    'timeSlots' => [],
                    'schedule' => null,
                    'hasVoted' => false,
                ]
            ]);
        }

        $allMatchIds = $allMatches->pluck('id');

        // Get time slots from the first match as the template, then aggregate votes across all matches
        $templateSlots = BuddyTimeSlot::where('match_id', $match->id)
            ->withCount('votes')
            ->get();

        // --- Auto-sync for mentees whose match has no slots yet (pre-fix data case) ---
        // If this mentee's match has no time slots but the mentor has published slots in
        // another match, copy those published slots here so the mentee can vote.
        if ($participant->role === 'mentee' && $templateSlots->isEmpty()) {
            $mentorId = $match->mentor_id; // BuddyParticipant ID of the mentor

            // Find a sibling match (same mentor) that already has published slots
            $siblingMatchIds = BuddyMatch::where('mentor_id', $mentorId)
                ->where('status', 'active')
                ->where('id', '!=', $match->id)
                ->pluck('id');

            $sourceMatchId = BuddyTimeSlot::whereIn('match_id', $siblingMatchIds)
                ->where('is_published', true)
                ->value('match_id');

            if ($sourceMatchId) {
                $sourceSlots = BuddyTimeSlot::where('match_id', $sourceMatchId)
                    ->where('is_published', true)
                    ->get();

                foreach ($sourceSlots as $sourceSlot) {
                    BuddyTimeSlot::firstOrCreate(
                        [
                            'match_id'   => $match->id,
                            'day'        => $sourceSlot->day,
                            'start_time' => $sourceSlot->start_time,
                            'end_time'   => $sourceSlot->end_time,
                        ],
                        ['is_published' => true]
                    );
                }

                // Ensure a voting BuddySchedule record exists for this match
                BuddySchedule::updateOrCreate(
                    ['match_id' => $match->id],
                    [
                        'day' => '',
                        'start_time' => '00:00:00',
                        'end_time' => '00:00:00',
                        'status' => 'voting',
                    ]
                );

                // Refresh templateSlots now that the slots have been copied
                $templateSlots = BuddyTimeSlot::where('match_id', $match->id)
                    ->withCount('votes')
                    ->get();
            }
        }
        // --- End auto-sync ---

        if ($participant->role === 'mentor' && $allMatches->count() > 1) {
            // Aggregate votes from equivalent slots across all mentor's matches
            $timeSlots = $templateSlots->map(function ($slot) use ($allMatchIds) {
                $totalVotes = BuddyTimeSlot::whereIn('match_id', $allMatchIds)
                    ->where('day', $slot->day)
                    ->where('start_time', $slot->start_time)
                    ->where('end_time', $slot->end_time)
                    ->withCount('votes')
                    ->get()
                    ->sum('votes_count');
                return [
                    'id' => (string)$slot->id,
                    'day' => $slot->day,
                    'startTime' => $slot->formatted_start_time,
                    'endTime' => $slot->formatted_end_time,
                    'votes' => $totalVotes,
                    'status' => $slot->is_published ? 'voting' : 'pending',
                ];
            });
        } else if ($allMatchIds->count() > 1) {
            // Mentee: aggregate votes across all sibling matches so vote counts are consistent
            $timeSlots = $templateSlots->map(function ($slot) use ($allMatchIds) {
                $totalVotes = BuddyTimeSlot::whereIn('match_id', $allMatchIds)
                    ->where('day', $slot->day)
                    ->where('start_time', $slot->start_time)
                    ->where('end_time', $slot->end_time)
                    ->withCount('votes')
                    ->get()
                    ->sum('votes_count');
                return [
                    'id' => (string)$slot->id,
                    'day' => $slot->day,
                    'startTime' => $slot->formatted_start_time,
                    'endTime' => $slot->formatted_end_time,
                    'votes' => $totalVotes,
                    'status' => $slot->is_published ? 'voting' : 'pending',
                ];
            });
        } else {
            $timeSlots = $templateSlots->map(function ($slot) {
                return [
                    'id' => (string)$slot->id,
                    'day' => $slot->day,
                    'startTime' => $slot->formatted_start_time,
                    'endTime' => $slot->formatted_end_time,
                    'votes' => $slot->votes_count,
                    'status' => $slot->is_published ? 'voting' : 'pending',
                ];
            });
        }

        // Check if current participant has voted
        $hasVoted = BuddyTimeSlotVote::whereHas('timeSlot', function ($query) use ($match) {
                $query->where('match_id', $match->id);
            })
            ->where('participant_id', $participant->id)
            ->exists();

        // Get confirmed schedule if exists
        $schedule = BuddySchedule::where('match_id', $match->id)->first();
        $scheduledMeeting = null;

        if ($schedule && $schedule->status === 'confirmed') {
            $scheduledMeeting = [
                'day' => $schedule->day,
                'time' => $schedule->formatted_time,
                'totalVotes' => $schedule->total_votes,
            ];
        }

        // Check if slots are published (across any of the mentor's matches, or the mentee's match)
        $slotsPublished = BuddyTimeSlot::whereIn('match_id', $allMatchIds)
            ->where('is_published', true)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'hasMatch' => true,
                'matchId' => (string)$match->id,
                'timeSlots' => $timeSlots,
                'schedule' => $scheduledMeeting,
                'hasVoted' => $hasVoted,
                'isScheduled' => $schedule && $schedule->status === 'confirmed',
                'slotsPublished' => $slotsPublished,
            ]
        ]);
    }

    /**
     * Add a time slot (mentor only)
     */
    public function addTimeSlot(Request $request): JsonResponse
    {
        // Block writes on archived semesters
        if ($request->attributes->get('readonly')) {
            return response()->json(['success' => false, 'message' => 'This semester is archived and read-only'], 403);
        }

        $request->validate([
            'student_id' => 'required|string',
            'day' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // Use semester-scoped participant from middleware
        $participant = $request->attributes->get('participant')
            ?? BuddyParticipant::where('student_id', $request->student_id)->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not found'
            ], 404);
        }

        if ($participant->role !== 'mentor') {
            return response()->json([
                'success' => false,
                'message' => 'Only mentors can add time slots'
            ], 403);
        }

        // Get ALL active matches for this mentor (scoped to semester)
        $matches = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
            })
            ->where('status', 'active')
            ->when($participant->semester_id, fn($q, $sid) => $q->where('semester_id', $sid))
            ->get();

        if ($matches->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active match found'
            ], 404);
        }

        // Use first match to check published/duplicate state (all matches share same slots)
        $firstMatch = $matches->first();

        // Check if slots are already published on any match
        $slotsPublished = BuddyTimeSlot::whereIn('match_id', $matches->pluck('id'))
            ->where('is_published', true)
            ->exists();

        if ($slotsPublished) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add slots after publishing'
            ], 400);
        }

        // Check for duplicate time slot on the first match
        $duplicateExists = BuddyTimeSlot::where('match_id', $firstMatch->id)
            ->where('day', $request->day)
            ->where('start_time', $request->start_time)
            ->where('end_time', $request->end_time)
            ->exists();

        if ($duplicateExists) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot already exists. Please choose a different day or time.'
            ], 400);
        }

        // Create the time slot for ALL active matches so every mentee can see and vote
        $firstSlot = null;
        foreach ($matches as $match) {
            $slot = BuddyTimeSlot::create([
                'match_id' => $match->id,
                'day' => $request->day,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'is_published' => false,
            ]);
            if (!$firstSlot) {
                $firstSlot = $slot;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Time slot added successfully',
            'data' => [
                'id' => (string)$firstSlot->id,
                'day' => $firstSlot->day,
                'startTime' => $firstSlot->formatted_start_time,
                'endTime' => $firstSlot->formatted_end_time,
                'votes' => 0,
                'status' => 'pending',
            ]
        ]);
    }

    /**
     * Remove a time slot (mentor only)
     */
    public function removeTimeSlot(Request $request, $slotId): JsonResponse
    {
        // Block writes on archived semesters
        if ($request->attributes->get('readonly')) {
            return response()->json(['success' => false, 'message' => 'This semester is archived and read-only'], 403);
        }

        $studentId = $request->query('student_id');

        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required'
            ], 400);
        }

        // Use semester-scoped participant from middleware
        $participant = $request->attributes->get('participant');
        if (!$participant) {
            $participant = BuddyParticipant::where('student_id', $studentId)->first();
        }

        if (!$participant || $participant->role !== 'mentor') {
            return response()->json([
                'success' => false,
                'message' => 'Only mentors can remove time slots'
            ], 403);
        }

        $timeSlot = BuddyTimeSlot::find($slotId);

        if (!$timeSlot) {
            return response()->json([
                'success' => false,
                'message' => 'Time slot not found'
            ], 404);
        }

        // Verify ownership through any active match (scoped to semester)
        $matchIds = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
            })
            ->where('status', 'active')
            ->when($participant->semester_id, fn($q, $sid) => $q->where('semester_id', $sid))
            ->pluck('id');

        if ($matchIds->isEmpty() || !$matchIds->contains($timeSlot->match_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized to remove this time slot'
            ], 403);
        }

        if ($timeSlot->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove published time slots'
            ], 400);
        }

        // Delete the equivalent slot (same day/time) from ALL mentor's active matches
        BuddyTimeSlot::whereIn('match_id', $matchIds)
            ->where('day', $timeSlot->day)
            ->where('start_time', $timeSlot->start_time)
            ->where('end_time', $timeSlot->end_time)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Time slot removed successfully'
        ]);
    }

    /**
     * Publish time slots for voting (mentor only)
     */
    public function publishTimeSlots(Request $request): JsonResponse
    {
        // Block writes on archived semesters
        if ($request->attributes->get('readonly')) {
            return response()->json(['success' => false, 'message' => 'This semester is archived and read-only'], 403);
        }

        $request->validate([
            'student_id' => 'required|string',
        ]);

        // Use semester-scoped participant from middleware
        $participant = $request->attributes->get('participant')
            ?? BuddyParticipant::where('student_id', $request->student_id)->first();

        if (!$participant || $participant->role !== 'mentor') {
            return response()->json([
                'success' => false,
                'message' => 'Only mentors can publish time slots'
            ], 403);
        }

        $matches = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
            })
            ->where('status', 'active')
            ->when($participant->semester_id, fn($q, $sid) => $q->where('semester_id', $sid))
            ->get();

        if ($matches->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active match found'
            ], 404);
        }

        // Check that at least one match has unpublished slots
        $hasSlots = BuddyTimeSlot::whereIn('match_id', $matches->pluck('id'))->exists();

        if (!$hasSlots) {
            return response()->json([
                'success' => false,
                'message' => 'No time slots to publish'
            ], 400);
        }

        // Step 1: Find the template match – the first match that already has slots
        $templateSlots = collect();
        foreach ($matches as $m) {
            $s = BuddyTimeSlot::where('match_id', $m->id)->get();
            if ($s->isNotEmpty()) {
                $templateSlots = $s;
                break;
            }
        }

        // Step 2: For every match that has NO slots, copy the template slots into it.
        //         Then publish all slots and create a BuddySchedule voting record.
        foreach ($matches as $match) {
            $existing = BuddyTimeSlot::where('match_id', $match->id)->count();
            if ($existing === 0) {
                foreach ($templateSlots as $tSlot) {
                    BuddyTimeSlot::create([
                        'match_id'   => $match->id,
                        'day'        => $tSlot->day,
                        'start_time' => $tSlot->start_time,
                        'end_time'   => $tSlot->end_time,
                        'is_published' => false,
                    ]);
                }
            }

            BuddyTimeSlot::where('match_id', $match->id)
                ->update(['is_published' => true]);

            BuddySchedule::updateOrCreate(
                ['match_id' => $match->id],
                [
                    'day' => '',
                    'start_time' => '00:00:00',
                    'end_time' => '00:00:00',
                    'status' => 'voting',
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Time slots published for voting'
        ]);
    }

    /**
     * Vote on time slots (mentee only) – supports multiple slot selections
     */
    public function voteTimeSlot(Request $request): JsonResponse
    {
        // Block writes on archived semesters
        if ($request->attributes->get('readonly')) {
            return response()->json(['success' => false, 'message' => 'This semester is archived and read-only'], 403);
        }

        $request->validate([
            'student_id' => 'required|string',
            'slot_ids'   => 'required|array|min:1',
            'slot_ids.*' => 'integer',
        ]);

        // Use semester-scoped participant from middleware
        $participant = $request->attributes->get('participant')
            ?? BuddyParticipant::where('student_id', $request->student_id)->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not found'
            ], 404);
        }

        if ($participant->role !== 'mentee') {
            return response()->json([
                'success' => false,
                'message' => 'Only mentees can vote on time slots'
            ], 403);
        }

        // Verify participant is matched (scoped to semester)
        $match = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentee');
            })
            ->where('status', 'active')
            ->when($participant->semester_id, fn($q, $sid) => $q->where('semester_id', $sid))
            ->first();

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'No active match found'
            ], 404);
        }

        // Check if already voted on any slot for this match
        $existingVote = BuddyTimeSlotVote::whereHas('timeSlot', function ($query) use ($match) {
                $query->where('match_id', $match->id);
            })
            ->where('participant_id', $participant->id)
            ->first();

        if ($existingVote) {
            return response()->json([
                'success' => false,
                'message' => 'You have already voted'
            ], 400);
        }

        // Validate all requested slots belong to this match and are published
        $slots = BuddyTimeSlot::whereIn('id', $request->slot_ids)
            ->where('match_id', $match->id)
            ->where('is_published', true)
            ->get();

        if ($slots->count() !== count($request->slot_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more time slots are invalid or not available for voting'
            ], 400);
        }

        // Record a vote for each selected slot
        foreach ($slots as $slot) {
            BuddyTimeSlotVote::create([
                'time_slot_id'   => $slot->id,
                'participant_id' => $participant->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Votes recorded successfully'
        ]);
    }

    /**
     * Confirm schedule based on votes (mentor only)
     */
    public function confirmSchedule(Request $request): JsonResponse
    {
        // Block writes on archived semesters
        if ($request->attributes->get('readonly')) {
            return response()->json(['success' => false, 'message' => 'This semester is archived and read-only'], 403);
        }

        $request->validate([
            'student_id' => 'required|string',
        ]);

        // Use semester-scoped participant from middleware
        $participant = $request->attributes->get('participant')
            ?? BuddyParticipant::where('student_id', $request->student_id)->first();

        if (!$participant || $participant->role !== 'mentor') {
            return response()->json([
                'success' => false,
                'message' => 'Only mentors can confirm schedule'
            ], 403);
        }

        $matches = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
            })
            ->where('status', 'active')
            ->when($participant->semester_id, fn($q, $sid) => $q->where('semester_id', $sid))
            ->get();

        if ($matches->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active match found'
            ], 404);
        }

        $firstMatch = $matches->first();
        $allMatchIds = $matches->pluck('id');

        // Find winning slot by aggregating votes across all matches' equivalent slots
        // Use first match slots as template, sum votes from all matches for each day/time
        $templateSlots = BuddyTimeSlot::where('match_id', $firstMatch->id)
            ->where('is_published', true)
            ->get();

        if ($templateSlots->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No time slots available'
            ], 400);
        }

        $bestSlot = null;
        $bestVotes = -1;
        foreach ($templateSlots as $template) {
            $totalVotes = BuddyTimeSlot::whereIn('match_id', $allMatchIds)
                ->where('day', $template->day)
                ->where('start_time', $template->start_time)
                ->where('end_time', $template->end_time)
                ->withCount('votes')
                ->get()
                ->sum('votes_count');
            if ($totalVotes > $bestVotes) {
                $bestVotes = $totalVotes;
                $bestSlot = $template;
            }
        }

        if (!$bestSlot) {
            return response()->json([
                'success' => false,
                'message' => 'No time slots available'
            ], 400);
        }

        $dayOfWeek = $bestSlot->day;
        $startTime  = $bestSlot->start_time;
        $endTime    = $bestSlot->end_time;
        $totalSessionsCreated = 0;

        // Determine session range from active semester setting
        $semesterSetting = BuddySemesterSetting::getActiveSemester();
        if ($semesterSetting) {
            $semStart = \Carbon\Carbon::parse($semesterSetting->start_date, 'Asia/Kuala_Lumpur');
            $semEnd   = \Carbon\Carbon::parse($semesterSetting->end_date, 'Asia/Kuala_Lumpur');
            $totalWeeks = $semesterSetting->total_weeks;
        } else {
            // Fallback: start from next occurrence of the day, 14 weeks
            $semStart = \Carbon\Carbon::now('Asia/Kuala_Lumpur');
            $semEnd   = \Carbon\Carbon::now('Asia/Kuala_Lumpur')->addWeeks(14);
            $totalWeeks = 14;
        }

        // Find first occurrence of the scheduled day on or after semester start
        $firstSessionDate = $semStart->copy();
        while ($firstSessionDate->format('l') !== $dayOfWeek) {
            $firstSessionDate->addDay();
        }

        // Confirm schedule and create sessions for ALL matches
        foreach ($matches as $match) {
            // Delete all existing PENDING sessions so we can cleanly regenerate
            // from the current semester setting (fixes stale 7-session / wrong-date data)
            BuddySession::where('match_id', $match->id)
                ->where('status', 'pending')
                ->delete();

            // Find the equivalent winning slot for this specific match
            $winningSlot = BuddyTimeSlot::where('match_id', $match->id)
                ->where('day', $dayOfWeek)
                ->where('start_time', $startTime)
                ->where('end_time', $endTime)
                ->first();

            BuddySchedule::updateOrCreate(
                ['match_id' => $match->id],
                [
                    'selected_slot_id' => $winningSlot ? $winningSlot->id : null,
                    'day' => $dayOfWeek,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'total_votes' => $bestVotes,
                    'status' => 'confirmed',
                ]
            );

            // Create sessions for each week within semester range
            for ($week = 0; $week < $totalWeeks; $week++) {
                $sessionDate = $firstSessionDate->copy()->addWeeks($week);
                // Stop if session date exceeds semester end date
                if ($sessionDate->gt($semEnd)) {
                    break;
                }
                $existing = BuddySession::where('match_id', $match->id)
                    ->where('session_date', $sessionDate->format('Y-m-d'))
                    ->first();
                if (!$existing) {
                    BuddySession::create([
                        'match_id' => $match->id,
                        'session_date' => $sessionDate->format('Y-m-d'),
                        'session_time' => $startTime,
                        'session_end_time' => $endTime,
                        'topic' => 'Week ' . ($week + 1) . ' Session',
                        'status' => 'pending',
                    ]);
                    $totalSessionsCreated++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Schedule confirmed successfully. {$totalSessionsCreated} weekly sessions created.",
            'data' => [
                'day' => $dayOfWeek,
                'time' => date('H:i', strtotime($startTime)) . ' - ' . date('H:i', strtotime($endTime)),
                'totalVotes' => $bestVotes,
                'sessionsCreated' => $totalSessionsCreated,
            ]
        ]);
    }

    /**
     * Reset all votes for the match so mentees can vote again (mentor only)
     */
    public function resetVotes(Request $request): JsonResponse
    {
        // Block writes on archived semesters
        if ($request->attributes->get('readonly')) {
            return response()->json(['success' => false, 'message' => 'This semester is archived and read-only'], 403);
        }

        $request->validate([
            'student_id' => 'required|string',
        ]);

        // Use semester-scoped participant from middleware
        $participant = $request->attributes->get('participant')
            ?? BuddyParticipant::where('student_id', $request->student_id)->first();

        if (!$participant || $participant->role !== 'mentor') {
            return response()->json([
                'success' => false,
                'message' => 'Only mentors can reset votes'
            ], 403);
        }

        $allMatchIds = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
            })
            ->where('status', 'active')
            ->when($participant->semester_id, fn($q, $sid) => $q->where('semester_id', $sid))
            ->pluck('id');

        if ($allMatchIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active match found'
            ], 404);
        }

        // Delete all votes for ALL mentor matches' time slots
        BuddyTimeSlotVote::whereHas('timeSlot', function ($query) use ($allMatchIds) {
            $query->whereIn('match_id', $allMatchIds);
        })->delete();

        return response()->json([
            'success' => true,
            'message' => 'Votes reset successfully. Mentees can now vote again.'
        ]);
    }

    /**
     * Get mentor dashboard data with all mentees
     */
    public function getMentorDashboard(Request $request): JsonResponse
    {
        $studentId  = $request->query('student_id');
        $semesterId = $request->query('semester_id');

        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required'
            ], 400);
        }

        // Resolve target semester
        $targetSemester = $semesterId
            ? BuddySemesterSetting::find($semesterId)
            : BuddySemesterSetting::getActiveSemester();

        // Get mentor participant info — scoped to semester when available
        $mentorQuery = BuddyParticipant::with('subject')
            ->where('student_id', $studentId)
            ->where('role', 'mentor');

        if ($targetSemester) {
            $mentorQuery->where('semester_id', $targetSemester->id);
        }

        $mentor = $mentorQuery->first();

        // Fallback: if no record exists for the active/requested semester (e.g. new semester
        // just activated but this mentor registered in a previous semester), load their most
        // recent mentor record so the dashboard still works (will be shown read-only).
        if (!$mentor && !$semesterId) {
            $mentor = BuddyParticipant::with('subject')
                ->where('student_id', $studentId)
                ->where('role', 'mentor')
                ->orderByDesc('created_at')
                ->first();
        }

        if (!$mentor) {
            return response()->json([
                'success' => false,
                'message' => 'Mentor not found'
            ], 404);
        }

        // Determine read-only mode
        $activeSemester = BuddySemesterSetting::getActiveSemester();
        $isReadonly = $activeSemester && $mentor->semester_id !== null
            && $mentor->semester_id !== $activeSemester->id;

        // Get all active matches for this mentor via pivot table
        $matches = BuddyMatch::whereHas('participants', function ($query) use ($mentor) {
                $query->where('buddy_match_participants.participant_id', $mentor->id)
                      ->where('buddy_match_participants.role', 'mentor');
            })
            ->when($mentor->semester_id, fn($q) => $q->where('semester_id', $mentor->semester_id))
            ->with(['mentees', 'subject', 'sessions'])
            ->where('status', 'active')
            ->get();

        // Build mentees list with attendance data
        $mentees = [];
        $allMeetings = [];
        $meetingsMap  = [];
        $allAttendanceRecords = [];
        $uniqueSessionKeys = []; // Track unique sessions by date+time to avoid counting shared group sessions multiple times
        $totalCompletedSessions = 0;
        $totalSessions = 0;
        $totalSessionsUpToToday = 0;
        $today = now()->toDateString();

        foreach ($matches as $match) {
            // Get all mentees in this match
            $menteesInMatch = $match->mentees()->get();
            
            // Get sessions for this match — filter to sessions up to today for attendance
            $sessions = $match->sessions ?? collect();
            $sessionsUpToTodayForMatch = $sessions->filter(fn($s) => $s->session_date->format('Y-m-d') <= $today);
            $completedSessionsForMatch = $sessionsUpToTodayForMatch->where('status', 'completed')->count();
            $totalSessionsForMatch = $sessionsUpToTodayForMatch->count();
            
            // Deduplicate sessions by date+time — a mentor with 3 mentees holds ONE shared
            // session, but the DB has one session row per match. Count each unique
            // date+time combination only once across all matches.
            foreach ($sessions as $session) {
                $sessionKey = $session->session_date->format('Y-m-d') . '|' . ($session->session_time ?? '');
                if (!isset($uniqueSessionKeys[$sessionKey])) {
                    $uniqueSessionKeys[$sessionKey] = true;
                    $totalSessions++;
                    if ($session->session_date->format('Y-m-d') <= $today) {
                        $totalSessionsUpToToday++;
                    }
                    if ($session->status === 'completed') {
                        $totalCompletedSessions++;
                    }
                }
            }
            
            foreach ($menteesInMatch as $mentee) {
                // Calculate attendance rate for this mentee based on match sessions
                $attendanceRate = $totalSessionsForMatch > 0 
                    ? round(($completedSessionsForMatch / $totalSessionsForMatch) * 100) 
                    : 0;

                $mentees[] = [
                    'id' => (string)$mentee->id,
                    'name' => $mentee->full_name,
                    'studentId' => $mentee->student_id,
                    'subject' => $match->subject ? $match->subject->name : ($mentee->subject ? $mentee->subject->name : 'N/A'),
                    'isRepeater' => $mentee->is_repeater ?? false,
                    'attendanceRate' => $attendanceRate,
                    'completedSessions' => $completedSessionsForMatch,
                    'totalSessions' => $totalSessionsForMatch,
                ];

                // Build attendance records for this mentee (only sessions up to today)
                foreach ($sessions as $session) {
                    if ($session->session_date->format('Y-m-d') > $today) {
                        continue; // skip future sessions
                    }

                    // Check mentor check-in: if not set on this session, look at sibling sessions
                    $mentorCheckedIn = $session->mentor_check_in !== null;
                    if (!$mentorCheckedIn) {
                        $siblingMentorSession = BuddySession::whereIn('match_id', $matches->pluck('id'))
                            ->where('id', '!=', $session->id)
                            ->where('session_date', $session->session_date)
                            ->where('session_time', $session->session_time)
                            ->whereNotNull('mentor_check_in')
                            ->first();
                        if ($siblingMentorSession) {
                            $mentorCheckedIn = true;
                        }
                    }

                    $allAttendanceRecords[] = [
                        'id' => (string)$session->id,
                        'menteeId' => (string)$mentee->id,
                        'menteeName' => $mentee->full_name,
                        'date' => $session->session_date->format('Y-m-d'),
                        'topic' => $session->topic ?? 'Session',
                        'status' => $session->mentee_check_in ? 'present' : 'absent',
                        'mentorCheckedIn' => $mentorCheckedIn,
                    ];
                }
            }

            // Add meetings once per DATE using keyed map to avoid duplicates across same-date sessions
            foreach ($sessions as $session) {
                $dateKey = $session->session_date->format('Y-m-d');
                if (!isset($meetingsMap[$dateKey])) {
                    $meetingsMap[$dateKey] = [
                        'id' => (string)$session->id,
                        'matchId' => (string)$match->id,
                        'subject' => $match->subject ? $match->subject->name : 'Session',
                        'date' => $session->session_date->format('Y-m-d'),
                        'time' => $session->session_time,
                        'topic' => $session->topic ?? 'Session',
                        'description' => $session->description,
                        'status' => $session->status,
                        'mentorCheckIn' => $session->mentor_check_in ? $session->mentor_check_in->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s') : null,
                        'menteeCheckIn' => $session->mentee_check_in ? $session->mentee_check_in->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s') : null,
                    ];
                }
            }
        }

        $allMeetings = array_values($meetingsMap);

        // Sort meetings by date descending
        usort($allMeetings, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        // Get upcoming meetings (today and future dates, sorted nearest first)
        $upcomingMeetings = array_filter($allMeetings, function($meeting) use ($today) {
            return $meeting['date'] >= $today;
        });
        // Sort ascending by date so the nearest session comes first
        usort($upcomingMeetings, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        $upcomingMeetings = array_slice($upcomingMeetings, 0, 5);

        // Calculate overall stats — attendance based on sessions up to today only
        $overallAttendanceRate = $totalSessionsUpToToday > 0 
            ? round(($totalCompletedSessions / $totalSessionsUpToToday) * 100) 
            : 0;

        // Get mentor's subjects
        $mentorSubjects = $matches->pluck('subject.name')->filter()->unique()->values()->toArray();
        if (empty($mentorSubjects) && $mentor->subject) {
            $mentorSubjects = [$mentor->subject->name];
        }

        // Get confirmed weekly schedules for all matches — deduplicate by day+time
        $weeklySchedules = [];
        $scheduleSeen = []; // key: "day|start_time|end_time" — only emit one entry per unique schedule
        foreach ($matches as $match) {
            $confirmedSchedule = BuddySchedule::where('match_id', $match->id)
                // ->where('status', 'confirmed')
                ->first();
            
            if ($confirmedSchedule) {
                $dedupeKey = $confirmedSchedule->day . '|' . $confirmedSchedule->start_time . '|' . $confirmedSchedule->end_time;
                if (isset($scheduleSeen[$dedupeKey])) {
                    continue; // same day+time already emitted
                }
                $scheduleSeen[$dedupeKey] = true;

                $mentee = $match->mentee;
                $weeklySchedules[] = [
                    'matchId' => (string)$match->id,
                    'menteeId' => (string)$mentee->id,
                    'menteeName' => $mentee->full_name,
                    'subject' => $match->subject ? $match->subject->name : 'Session',
                    'day' => $confirmedSchedule->day,
                    'time' => $confirmedSchedule->formatted_time,
                    'startTime' => date('H:i', strtotime($confirmedSchedule->start_time)),
                    'endTime' => date('H:i', strtotime($confirmedSchedule->end_time)),
                ];
            }
        }

        $response = [
            'mentor' => [
                'id' => $mentor->id,
                'name' => $mentor->full_name,
                'studentId' => $mentor->student_id,
                'role' => 'mentor',
                'status' => $mentor->status,
                'rating' => (float)($mentor->rating ?? 5.0),
                'faculty' => $mentor->faculty,
                'course' => $mentor->course,
                'totalMentees' => count($mentees),
                'subjects' => $mentorSubjects,
            ],
            'mentees' => $mentees,
            'meetings' => $allMeetings,
            'upcomingMeetings' => array_values($upcomingMeetings),
            'weeklySchedules' => $weeklySchedules,
            'attendanceRecords' => $allAttendanceRecords,
            'stats' => [
                'totalMentees' => count($mentees),
                'totalSessions' => $totalSessions,
                'completedSessions' => $totalCompletedSessions,
                'attendanceRate' => $overallAttendanceRate,
                'upcomingMeetings' => $totalSessions - $totalCompletedSessions,
            ],
            'is_readonly' => $isReadonly,
            'semester_id' => $mentor->semester_id,
        ];

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Submit attendance for a session (mentor marking mentee attendance)
     * The mentor selects an existing auto-created session and optionally updates its topic/description
     */
    public function submitMentorAttendance(Request $request): JsonResponse
    {
        // Block writes on archived semesters
        if ($request->attributes->get('readonly')) {
            return response()->json(['success' => false, 'message' => 'This semester is archived and read-only'], 403);
        }

        try {
            $request->validate([
                'student_id' => 'required|string',
                'session_id' => 'required|integer',
                'session_topic' => 'nullable|string',
                'session_description' => 'nullable|string',
                'attendance' => 'required|array',
            ]);

            // Use semester-scoped participant from middleware
            $mentor = $request->attributes->get('participant');
            if (!$mentor || $mentor->role !== 'mentor') {
                $mentor = BuddyParticipant::where('student_id', $request->student_id)
                    ->where('role', 'mentor')
                    ->first();
            }

            if (!$mentor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mentor not found'
                ], 404);
            }

            // Find the session
            $session = BuddySession::find($request->session_id);
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            // If mentor provided a custom topic or description, update the session
            if ($request->session_topic) {
                $session->topic = $request->session_topic;
            }
            if ($request->session_description) {
                $session->description = $request->session_description;
            }

            // Record mentor check-in
            $session->mentor_check_in = now();

            // Find the mentee for THIS session's match to determine attendance
            $sessionMenteePivot = \DB::table('buddy_match_participants')
                ->where('match_id', $session->match_id)
                ->where('role', 'mentee')
                ->first();

            // Check if this session's mentee is marked present
            $thisSessionMenteePresent = false;
            if ($sessionMenteePivot) {
                $menteeParticipantId = (string)$sessionMenteePivot->participant_id;
                $thisSessionMenteePresent = ($request->attendance[$menteeParticipantId] ?? 'absent') === 'present';
            }

            if ($thisSessionMenteePresent) {
                $session->mentee_check_in = now();
                $session->status = 'completed';
            } else {
                $session->mentee_check_in = null;
                $session->status = 'pending';
            }

            $session->save();

            // Update match completed sessions count
            $match = $session->match;
            if ($match) {
                $match->completed_sessions = BuddySession::where('match_id', $match->id)
                    ->where('status', 'completed')
                    ->count();
                $match->save();
            }

            // Propagate to sibling sessions (same date/time across all mentor's matches)
            $mentorMatchIds = BuddyMatch::whereHas('participants', function ($q) use ($mentor) {
                $q->where('buddy_match_participants.participant_id', $mentor->id)
                  ->where('buddy_match_participants.role', 'mentor');
            })
            ->where('status', 'active')
            ->when($mentor->semester_id, fn($q) => $q->where('semester_id', $mentor->semester_id))
            ->pluck('id');

            $siblingSessions = BuddySession::whereIn('match_id', $mentorMatchIds)
                ->where('id', '!=', $session->id)
                ->where('session_date', $session->session_date)
                ->where('session_time', $session->session_time)
                ->get();

            foreach ($siblingSessions as $siblingSession) {
                $siblingSession->mentor_check_in = $session->mentor_check_in;
                $siblingSession->topic = $session->topic;
                $siblingSession->description = $session->description;

                // Find the mentee for this sibling session's match
                $siblingMenteePivot = \DB::table('buddy_match_participants')
                    ->where('match_id', $siblingSession->match_id)
                    ->where('role', 'mentee')
                    ->first();

                if ($siblingMenteePivot) {
                    $siblingMenteeId = (string)$siblingMenteePivot->participant_id;
                    $siblingMenteeStatus = $request->attendance[$siblingMenteeId] ?? 'absent';

                    if ($siblingMenteeStatus === 'present') {
                        $siblingSession->mentee_check_in = now();
                        $siblingSession->status = 'completed';
                    } else {
                        $siblingSession->mentee_check_in = null;
                        $siblingSession->status = 'pending';
                    }
                }

                $siblingSession->save();

                // Update sibling match completed sessions count
                $siblingMatch = $siblingSession->match;
                if ($siblingMatch) {
                    $siblingMatch->completed_sessions = BuddySession::where('match_id', $siblingMatch->id)
                        ->where('status', 'completed')
                        ->count();
                    $siblingMatch->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance submitted successfully',
                'data' => [
                    'sessionId' => $session->id,
                    'status' => $session->status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting attendance: ' . $e->getMessage()
            ], 500);
        }
    }
}
