<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Models\BuddyMatch;
use App\Models\BuddyParticipant;
use App\Models\BuddySchedule;
use App\Models\BuddySession;
use App\Models\BuddyTimeSlot;
use App\Models\BuddyTimeSlotVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get user dashboard data
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $studentId = $request->query('student_id');

        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required'
            ], 400);
        }

        // Get participant info
        $participant = BuddyParticipant::with('subject')
            ->where('student_id', $studentId)
            ->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not found'
            ], 404);
        }

        // Get active match with partner info
        $match = null;
        $partner = null;

        if ($participant->role === 'mentor') {
            // Get first active match via pivot table
            $matchRecord = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                    $query->where('buddy_match_participants.participant_id', $participant->id)
                          ->where('buddy_match_participants.role', 'mentor');
                })
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
            // (seeded data may have total_sessions=0 even with real session records)
            $totalSessions = ($match->total_sessions > 0) ? $match->total_sessions : count($sessionRecords);
            $completedSessions = ($match->completed_sessions > 0) ? $match->completed_sessions : $completedSessions;
        }

        // Calculate attendance rate
        $attendanceRate = $totalSessions > 0 
            ? round(($completedSessions / $totalSessions) * 100) 
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

        $participant = BuddyParticipant::where('student_id', $studentId)->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not found'
            ], 404);
        }

        // Get active match
        $matchQuery = $participant->role === 'mentor' 
            ? BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
              })
            : BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentee');
              });

        $match = $matchQuery->where('status', 'active')->first();

        if (!$match) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $sessions = BuddySession::where('match_id', $match->id)
            ->orderBy('session_date', 'desc')
            ->get()
            ->map(function ($session) {
                // Auto-mark sessions as missed if session time has passed and mentee hasn't checked in
                if ($session->status === 'pending' && !$session->mentee_check_in) {
                    $sessionDateStr = $session->session_date->format('Y-m-d');
                    $sessionEndTime = $session->session_end_time ?? '23:59:59';
                    $sessionDeadline = \Carbon\Carbon::parse($sessionDateStr . ' ' . $sessionEndTime);
                    
                    if (now()->gt($sessionDeadline)) {
                        $session->status = 'missed';
                        $session->save();
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
                    'mentorCheckIn' => $session->mentor_check_in ? $session->mentor_check_in->format('Y-m-d H:i:s') : null,
                    'menteeCheckIn' => $session->mentee_check_in ? $session->mentee_check_in->format('Y-m-d H:i:s') : null,
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
        $request->validate([
            'student_id' => 'required|string',
        ]);

        $participant = BuddyParticipant::where('student_id', $request->student_id)->first();

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
            
            // Combine date and end time to create deadline
            $sessionDeadline = \Carbon\Carbon::parse($sessionDateStr . ' ' . $sessionEndTime);
            $now = now();
            
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

        return response()->json([
            'success' => true,
            'message' => 'Check-in recorded successfully',
            'data' => [
                'sessionId' => $session->id,
                'status' => $session->status,
                'mentorCheckIn' => $session->mentor_check_in ? $session->mentor_check_in->format('Y-m-d H:i:s') : null,
                'menteeCheckIn' => $session->mentee_check_in ? $session->mentee_check_in->format('Y-m-d H:i:s') : null,
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

        $participant = BuddyParticipant::where('student_id', $studentId)->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not found'
            ], 404);
        }

        // Get active match
        $matchQuery = $participant->role === 'mentor'
            ? BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
              })
            : BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentee');
              });

        $match = $matchQuery->where('status', 'active')->first();

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

        // Get time slots with vote counts
        $timeSlots = BuddyTimeSlot::where('match_id', $match->id)
            ->withCount('votes')
            ->get()
            ->map(function ($slot) {
                return [
                    'id' => (string)$slot->id,
                    'day' => $slot->day,
                    'startTime' => $slot->formatted_start_time,
                    'endTime' => $slot->formatted_end_time,
                    'votes' => $slot->votes_count,
                    'status' => $slot->is_published ? 'voting' : 'pending',
                ];
            });

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

        // Check if slots are published (for mentee voting)
        $slotsPublished = BuddyTimeSlot::where('match_id', $match->id)
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
        $request->validate([
            'student_id' => 'required|string',
            'day' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $participant = BuddyParticipant::where('student_id', $request->student_id)->first();

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

        // Get active match
        $match = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
            })
            ->where('status', 'active')
            ->first();

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'No active match found'
            ], 404);
        }

        // Check if slots are already published
        $slotsPublished = BuddyTimeSlot::where('match_id', $match->id)
            ->where('is_published', true)
            ->exists();

        if ($slotsPublished) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add slots after publishing'
            ], 400);
        }

        // Check for duplicate time slot
        $duplicateExists = BuddyTimeSlot::where('match_id', $match->id)
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

        $timeSlot = BuddyTimeSlot::create([
            'match_id' => $match->id,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_published' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Time slot added successfully',
            'data' => [
                'id' => (string)$timeSlot->id,
                'day' => $timeSlot->day,
                'startTime' => $timeSlot->formatted_start_time,
                'endTime' => $timeSlot->formatted_end_time,
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
        $studentId = $request->query('student_id');

        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required'
            ], 400);
        }

        $participant = BuddyParticipant::where('student_id', $studentId)->first();

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

        // Verify ownership through match
        $match = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
            })
            ->where('status', 'active')
            ->first();

        if (!$match || $timeSlot->match_id !== $match->id) {
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

        $timeSlot->delete();

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
        $request->validate([
            'student_id' => 'required|string',
        ]);

        $participant = BuddyParticipant::where('student_id', $request->student_id)->first();

        if (!$participant || $participant->role !== 'mentor') {
            return response()->json([
                'success' => false,
                'message' => 'Only mentors can publish time slots'
            ], 403);
        }

        $match = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
            })
            ->where('status', 'active')
            ->first();

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'No active match found'
            ], 404);
        }

        $slots = BuddyTimeSlot::where('match_id', $match->id)->get();

        if ($slots->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No time slots to publish'
            ], 400);
        }

        // Publish all slots
        BuddyTimeSlot::where('match_id', $match->id)
            ->update(['is_published' => true]);

        // Create schedule record in voting status
        BuddySchedule::updateOrCreate(
            ['match_id' => $match->id],
            [
                'day' => '',
                'start_time' => '00:00:00',
                'end_time' => '00:00:00',
                'status' => 'voting',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Time slots published for voting'
        ]);
    }

    /**
     * Vote on a time slot (mentee only)
     */
    public function voteTimeSlot(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|string',
            'slot_id' => 'required|integer',
        ]);

        $participant = BuddyParticipant::where('student_id', $request->student_id)->first();

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

        $timeSlot = BuddyTimeSlot::find($request->slot_id);

        if (!$timeSlot || !$timeSlot->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Time slot not found or not available for voting'
            ], 404);
        }

        // Verify participant is matched to this slot's match
        $match = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentee');
            })
            ->where('status', 'active')
            ->first();

        if (!$match || $timeSlot->match_id !== $match->id) {
            return response()->json([
                'success' => false,
                'message' => 'Not authorized to vote on this time slot'
            ], 403);
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

        // Record vote
        BuddyTimeSlotVote::create([
            'time_slot_id' => $timeSlot->id,
            'participant_id' => $participant->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vote recorded successfully'
        ]);
    }

    /**
     * Confirm schedule based on votes (mentor only)
     */
    public function confirmSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|string',
        ]);

        $participant = BuddyParticipant::where('student_id', $request->student_id)->first();

        if (!$participant || $participant->role !== 'mentor') {
            return response()->json([
                'success' => false,
                'message' => 'Only mentors can confirm schedule'
            ], 403);
        }

        $match = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                      ->where('buddy_match_participants.role', 'mentor');
            })
            ->where('status', 'active')
            ->first();

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'No active match found'
            ], 404);
        }

        // Find slot with most votes
        $winningSlot = BuddyTimeSlot::where('match_id', $match->id)
            ->where('is_published', true)
            ->withCount('votes')
            ->orderBy('votes_count', 'desc')
            ->first();

        if (!$winningSlot) {
            return response()->json([
                'success' => false,
                'message' => 'No time slots available'
            ], 400);
        }

        // Update or create confirmed schedule
        $schedule = BuddySchedule::updateOrCreate(
            ['match_id' => $match->id],
            [
                'selected_slot_id' => $winningSlot->id,
                'day' => $winningSlot->day,
                'start_time' => $winningSlot->start_time,
                'end_time' => $winningSlot->end_time,
                'total_votes' => $winningSlot->votes_count,
                'status' => 'confirmed',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Schedule confirmed successfully',
            'data' => [
                'day' => $schedule->day,
                'time' => $schedule->formatted_time,
                'totalVotes' => $schedule->total_votes,
            ]
        ]);
    }

    /**
     * Get mentor dashboard data with all mentees
     */
    public function getMentorDashboard(Request $request): JsonResponse
    {
        $studentId = $request->query('student_id');

        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required'
            ], 400);
        }

        // Get mentor participant info
        $mentor = BuddyParticipant::with('subject')
            ->where('student_id', $studentId)
            ->where('role', 'mentor')
            ->first();

        if (!$mentor) {
            return response()->json([
                'success' => false,
                'message' => 'Mentor not found'
            ], 404);
        }

        // Get all active matches for this mentor via pivot table
        $matches = BuddyMatch::whereHas('participants', function ($query) use ($mentor) {
                $query->where('buddy_match_participants.participant_id', $mentor->id)
                      ->where('buddy_match_participants.role', 'mentor');
            })
            ->with(['mentees', 'subject', 'sessions'])
            ->where('status', 'active')
            ->get();

        // Build mentees list with attendance data
        $mentees = [];
        $allMeetings = [];
        $allAttendanceRecords = [];
        $uniqueSessionIds = []; // Track unique sessions to avoid double-counting
        $totalCompletedSessions = 0;
        $totalSessions = 0;

        foreach ($matches as $match) {
            // Get all mentees in this match
            $menteesInMatch = $match->mentees()->get();
            
            // Get sessions for this match (count only once per match, not per mentee)
            $sessions = $match->sessions ?? collect();
            $completedSessionsForMatch = $sessions->where('status', 'completed')->count();
            $totalSessionsForMatch = $sessions->count();
            
            // Add session IDs to track unique sessions across all matches
            foreach ($sessions as $session) {
                if (!in_array($session->id, $uniqueSessionIds)) {
                    $uniqueSessionIds[] = $session->id;
                    // Count this session only once
                    $totalSessions++;
                    if ($session->status === 'completed') {
                        $totalCompletedSessions++;
                    }
                }
            }
            
            foreach ($menteesInMatch as $mentee) {
                // Calculate attendance rate for this mentee based on match sessions
                $attendanceRate = $totalSessionsForMatch > 0 
                    ? round(($completedSessionsForMatch / $totalSessionsForMatch) * 100) 
                    : 100;

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

                // Build attendance records for this mentee
                foreach ($sessions as $session) {
                    $allAttendanceRecords[] = [
                        'id' => (string)$session->id,
                        'menteeId' => (string)$mentee->id,
                        'menteeName' => $mentee->full_name,
                        'date' => $session->session_date->format('Y-m-d'),
                        'topic' => $session->topic ?? 'Session',
                        'status' => $session->mentee_check_in ? 'present' : 'absent',
                        'mentorCheckedIn' => $session->mentor_check_in !== null,
                    ];

                    // Add to meetings list
                    $allMeetings[] = [
                        'id' => (string)$session->id,
                        'matchId' => (string)$match->id,
                        'menteeId' => (string)$mentee->id,
                        'menteeName' => $mentee->full_name,
                        'subject' => $match->subject ? $match->subject->name : 'Session',
                        'date' => $session->session_date->format('Y-m-d'),
                        'time' => $session->session_time,
                        'topic' => $session->topic ?? 'Session',
                        'status' => $session->status,
                        'mentorCheckIn' => $session->mentor_check_in ? $session->mentor_check_in->format('Y-m-d H:i:s') : null,
                        'menteeCheckIn' => $session->mentee_check_in ? $session->mentee_check_in->format('Y-m-d H:i:s') : null,
                    ];
                }
            }
        }

        // Sort meetings by date descending
        usort($allMeetings, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        // Get upcoming meetings (pending status, future or today's date)
        $today = now()->format('Y-m-d');
        $upcomingMeetings = array_filter($allMeetings, function($meeting) use ($today) {
            return $meeting['status'] === 'pending' && $meeting['date'] >= $today;
        });
        $upcomingMeetings = array_slice(array_values($upcomingMeetings), 0, 5);

        // Calculate overall stats
        $overallAttendanceRate = $totalSessions > 0 
            ? round(($totalCompletedSessions / $totalSessions) * 100) 
            : 0;

        // Get mentor's subjects
        $mentorSubjects = $matches->pluck('subject.name')->filter()->unique()->values()->toArray();
        if (empty($mentorSubjects) && $mentor->subject) {
            $mentorSubjects = [$mentor->subject->name];
        }

        // Get confirmed weekly schedules for all matches
        $weeklySchedules = [];
        foreach ($matches as $match) {
            $confirmedSchedule = BuddySchedule::where('match_id', $match->id)
                // ->where('status', 'confirmed')
                ->first();
            
            if ($confirmedSchedule) {
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
            'meetings' => array_values($allMeetings),
            'upcomingMeetings' => array_values($upcomingMeetings),
            'weeklySchedules' => $weeklySchedules,
            'attendanceRecords' => $allAttendanceRecords,
            'stats' => [
                'totalMentees' => count($mentees),
                'totalSessions' => $totalSessions,
                'completedSessions' => $totalCompletedSessions,
                'attendanceRate' => $overallAttendanceRate,
                'upcomingMeetings' => count($upcomingMeetings),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Submit attendance for a session (mentor marking mentee attendance)
     */
    public function submitMentorAttendance(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'student_id' => 'required|string',
                'session_date' => 'required|date',
                'session_topic' => 'required|string',
                'session_time' => 'nullable|string',
                'session_end_time' => 'nullable|string',
                'attendance' => 'required|array',
            ]);

            $mentor = BuddyParticipant::where('student_id', $request->student_id)
                ->where('role', 'mentor')
                ->first();

            if (!$mentor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mentor not found'
                ], 404);
            }

            // Get all active matches for this mentor via pivot table
            $matchesCollection = BuddyMatch::whereHas('participants', function ($query) use ($mentor) {
                    $query->where('buddy_match_participants.participant_id', $mentor->id)
                          ->where('buddy_match_participants.role', 'mentor');
                })
                ->where('status', 'active')
                ->with('mentees')
                ->get();
            
            // Build a map of mentee_id => match for easy lookup
            $matches = [];
            foreach ($matchesCollection as $match) {
                $mentees = $match->mentees()->get();
                foreach ($mentees as $mentee) {
                    $matches[$mentee->id] = $match;
                }
            }

            $createdSessions = [];
            
            // Get session time from request or default
            $sessionTime = $request->session_time ?? '10:00:00';
            $sessionEndTime = $request->session_end_time ?? '11:00:00';

            foreach ($request->attendance as $menteeId => $status) {
                // Find match for this mentee
                $match = $matches->get((int)$menteeId);
                
                if (!$match) {
                    continue;
                }

                // Determine session status based on mentor marking:
                // - 'present' = mentor confirms mentee was present -> completed
                // - 'absent' = mentor records session but mentee needs to check in -> pending (mentee can still check in within session time)
                $sessionStatus = $status === 'present' ? 'completed' : 'pending';

                // Create or update session
                $session = BuddySession::updateOrCreate(
                    [
                        'match_id' => $match->id,
                        'session_date' => $request->session_date,
                    ],
                    [
                        'topic' => $request->session_topic,
                        'session_time' => $sessionTime,
                        'session_end_time' => $sessionEndTime,
                        'mentor_check_in' => now(),
                        'mentee_check_in' => $status === 'present' ? now() : null,
                        'status' => $sessionStatus,
                    ]
                );

                $createdSessions[] = [
                    'sessionId' => $session->id,
                    'menteeId' => $menteeId,
                    'status' => $session->status,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance submitted successfully',
                'data' => [
                    'sessions' => $createdSessions,
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
