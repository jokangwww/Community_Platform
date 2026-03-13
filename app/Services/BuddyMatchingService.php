<?php

namespace App\Services;

use App\Models\BuddyMatch;
use App\Models\BuddyParticipant;
use App\Models\BuddySetting;
use App\Models\BuddySemesterSetting;
use App\Notifications\BuddyMatchedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuddyMatchingService
{
    protected int $maxMenteesPerMentor = 3;

    /**
     * Run the auto-matching algorithm
     * 
     * @return array Results of the matching process
     */
    public function runAutoMatch(): array
    {
        $results = [
            'matches_created' => 0,
            'mentees_matched' => [],
            'mentees_unmatched' => [],
            'errors' => [],
        ];

        try {
            DB::beginTransaction();

            $mentors = $this->getAvailableMentors();

            // Get unmatched mentees sorted by priority
            $mentees = $this->getUnmatchedMentees();

            // Perform matching
            foreach ($mentees as $mentee) {
                $matched = $this->tryMatchMentee($mentee, $mentors);
                
                if ($matched) {
                    $results['matches_created']++;
                    $results['mentees_matched'][] = [
                        'id' => $mentee->id,
                        'name' => $mentee->full_name,
                        'subject' => $matched['subject'],
                        'mentor' => $matched['mentor'],
                    ];
                } else {
                    $results['mentees_unmatched'][] = [
                        'id' => $mentee->id,
                        'name' => $mentee->full_name,
                        'reason' => 'No available mentor with matching subject',
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get available mentors with remaining capacity
     * 
     * @return Collection
     */
    protected function getAvailableMentors(): Collection
    {
        $activeSemester = BuddySemesterSetting::getActiveSemester();

        return BuddyParticipant::mentors()
            ->where('status', 'active')
            ->when($activeSemester, fn($q) => $q->where('semester_id', $activeSemester->id))
            ->whereNotNull('subject_id')
            ->withCount(['mentorMatches' => function ($query) {
                $query->where('status', 'active');
            }])
            ->having('mentor_matches_count', '<', $this->maxMenteesPerMentor)
            ->with('subject')
            ->get()
            ->keyBy('id');
    }

    /**
     * Get unmatched mentees sorted by priority
     * 
     * @return Collection
     */
    protected function getUnmatchedMentees(): Collection
    {
        $priorityEnabled = BuddySetting::isPriorityAllocationEnabled();
        $activeSemester = BuddySemesterSetting::getActiveSemester();

        $query = BuddyParticipant::mentees()
            ->where('status', 'active')
            ->when($activeSemester, fn($q) => $q->where('semester_id', $activeSemester->id))
            ->whereNotNull('subject_id')
            ->whereDoesntHave('menteeMatches', function ($query) {
                $query->where('status', 'active');
            })
            ->with('subject');

        if ($priorityEnabled) {
            // Sort by priority tier (high -> normal -> low) then by registration date
            $query->orderByRaw("CASE 
                WHEN priority_tier = 'high' THEN 1 
                WHEN priority_tier = 'normal' THEN 2 
                WHEN priority_tier = 'low' THEN 3 
                ELSE 4 
            END")
            ->orderBy('created_at', 'asc');
        } else {
            // first-come, first-served
            $query->orderBy('created_at', 'asc');
        }

        return $query->get();
    }

    /**
     * Try to match a mentee with an available mentor
     * 
     * @param BuddyParticipant $mentee
     * @param Collection $mentors (passed by reference to update capacity)
     * @return array|null Match info or null if no match found
     */
    protected function tryMatchMentee(BuddyParticipant $mentee, Collection &$mentors): ?array
    {
        $menteeSubjectId = $mentee->subject_id;

        if (!$menteeSubjectId) {
            return null;
        }

        // Find mentor
        foreach ($mentors as $mentorId => $mentor) {
            if ($mentor->mentor_matches_count >= $this->maxMenteesPerMentor) {
                continue;
            }

            if ($mentor->subject_id === $menteeSubjectId) {
                $match = BuddyMatch::create([
                    'mentor_id' => $mentor->id,
                    'mentee_id' => $mentee->id,
                    'subject_id' => $menteeSubjectId,
                    'matched_date' => now(),
                    'status' => 'active',
                    'semester_id' => $mentee->semester_id,
                    'total_sessions' => 0,
                    'completed_sessions' => 0,
                ]);

                $match->participants()->syncWithoutDetaching([
                    $mentor->id => ['role' => 'mentor'],
                    $mentee->id => ['role' => 'mentee'],
                ]);

                $mentor->mentor_matches_count++;

                $subjectName = $mentee->subject->name ?? 'N/A';
                if ($mentor->user) {
                    $mentor->user->notify(new BuddyMatchedNotification($match, $mentee->full_name, $subjectName, 'mentor'));
                }
                if ($mentee->user) {
                    $mentee->user->notify(new BuddyMatchedNotification($match, $mentor->full_name, $subjectName, 'mentee'));
                }

                return [
                    'match_id' => $match->id,
                    'mentor' => $mentor->full_name,
                    'subject' => $mentee->subject->name,
                ];
            }
        }

        return null;
    }

    /**
     * Match a specific mentee to a specific mentor for a subject
     * 
     * @param int $menteeId
     * @param int $mentorId
     * @param int|null $subjectId (optional - if null, uses mentee's subject)
     * @return BuddyMatch|null
     */
    public function createManualMatch(int $menteeId, int $mentorId, ?int $subjectId = null): ?BuddyMatch
    {
        // Verify mentee exists and is unmatched
        $mentee = BuddyParticipant::mentees()
            ->where('status', 'active')
            ->whereDoesntHave('menteeMatches', function ($query) {
                $query->where('status', 'active');
            })
            ->find($menteeId);

        if (!$mentee) {
            return null;
        }

        // Verify mentor exists and has capacity
        $mentor = BuddyParticipant::mentors()
            ->where('status', 'active')
            ->withCount(['mentorMatches' => function ($query) {
                $query->where('status', 'active');
            }])
            ->find($mentorId);

        if (!$mentor || $mentor->mentor_matches_count >= $this->maxMenteesPerMentor) {
            return null;
        }

        // Use provided subject ID or default to mentee's subject
        $finalSubjectId = $subjectId ?? $mentee->subject_id;

        if (!$finalSubjectId) {
            return null;
        }

        // Check if match already exists
        $existingMatch = BuddyMatch::where('mentor_id', $mentorId)
            ->where('mentee_id', $menteeId)
            ->where('status', 'active')
            ->first();

        if ($existingMatch) {
            return null;
        }

        $match = BuddyMatch::create([
            'mentor_id' => $mentorId,
            'mentee_id' => $menteeId,
            'subject_id' => $finalSubjectId,
            'matched_date' => now(),
            'status' => 'active',
            'semester_id' => $mentee->semester_id,
            'total_sessions' => 0,
            'completed_sessions' => 0,
        ]);

        // Populate the pivot table so participant-based queries work
        $match->participants()->syncWithoutDetaching([
            $mentorId => ['role' => 'mentor'],
            $menteeId => ['role' => 'mentee'],
        ]);

        // Notify both mentor and mentee about the match
        $subjectName = $mentee->subject->name ?? 'N/A';
        $mentor = BuddyParticipant::find($mentorId);
        if ($mentor && $mentor->user) {
            $mentor->user->notify(new BuddyMatchedNotification($match, $mentee->full_name, $subjectName, 'mentor'));
        }
        if ($mentee->user) {
            $mentee->user->notify(new BuddyMatchedNotification($match, $mentor->full_name ?? 'Mentor', $subjectName, 'mentee'));
        }

        return $match;
    }

    /**
     * Get matching statistics
     * 
     * @return array
     */
    public function getMatchingStats(?int $semesterId = null): array
    {
        if ($semesterId) {
            $targetSemesterId = $semesterId;
        } else {
            $activeSemester = BuddySemesterSetting::getActiveSemester();
            $targetSemesterId = $activeSemester?->id;
        }

        $totalMentors = BuddyParticipant::mentors()->where('status', 'active')
            ->when($targetSemesterId, fn($q) => $q->where('semester_id', $targetSemesterId))->count();
        $totalMentees = BuddyParticipant::mentees()->where('status', 'active')
            ->when($targetSemesterId, fn($q) => $q->where('semester_id', $targetSemesterId))->count();
        
        $matchedMentees = BuddyParticipant::mentees()
            ->where('status', 'active')
            ->when($targetSemesterId, fn($q) => $q->where('semester_id', $targetSemesterId))
            ->whereHas('menteeMatches', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        $unmatchedMentees = $totalMentees - $matchedMentees;

        $availableMentorSlots = BuddyParticipant::mentors()
            ->where('status', 'active')
            ->when($targetSemesterId, fn($q) => $q->where('semester_id', $targetSemesterId))
            ->withCount(['mentorMatches' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get()
            ->sum(function ($mentor) {
                return max(0, $this->maxMenteesPerMentor - $mentor->mentor_matches_count);
            });

        return [
            'total_mentors' => $totalMentors,
            'total_mentees' => $totalMentees,
            'matched_mentees' => $matchedMentees,
            'unmatched_mentees' => $unmatchedMentees,
            'available_mentor_slots' => $availableMentorSlots,
            'active_matches' => BuddyMatch::where('status', 'active')
                ->when($targetSemesterId, fn($q) => $q->where('semester_id', $targetSemesterId))
                ->count(),
        ];
    }

    /**
     * Preview Auto Match
     * 
     * @return array
     */
    public function previewAutoMatch(): array
    {
        $results = [
            'potential_matches' => [],
            'unmatched_mentees' => [],
        ];

        // Get available mentors (clone to not affect real data)
        $mentors = $this->getAvailableMentors();
        $mentorCapacity = [];
        foreach ($mentors as $mentor) {
            $mentorCapacity[$mentor->id] = $this->maxMenteesPerMentor - $mentor->mentor_matches_count;
        }

        // Get unmatched mentees
        $mentees = $this->getUnmatchedMentees();

        foreach ($mentees as $mentee) {
            $menteeSubjectId = $mentee->subject_id;
            $matched = false;

            if ($menteeSubjectId) {
                foreach ($mentors as $mentorId => $mentor) {
                    if (($mentorCapacity[$mentorId] ?? 0) <= 0) {
                        continue;
                    }

                    // Direct subject comparison
                    if ($mentor->subject_id === $menteeSubjectId) {
                        $results['potential_matches'][] = [
                            'mentee' => [
                                'id' => $mentee->id,
                                'name' => $mentee->full_name,
                                'priority_tier' => $mentee->priority_tier,
                                'is_repeater' => $mentee->is_repeater,
                            ],
                            'mentor' => [
                                'id' => $mentor->id,
                                'name' => $mentor->full_name,
                            ],
                            'subject' => [
                                'id' => $menteeSubjectId,
                                'name' => $mentee->subject->name ?? 'Unknown',
                            ],
                        ];

                        $mentorCapacity[$mentorId]--;
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                $results['unmatched_mentees'][] = [
                    'id' => $mentee->id,
                    'name' => $mentee->full_name,
                    'priority_tier' => $mentee->priority_tier,
                    'subject' => $mentee->subject ? $mentee->subject->name : 'No subject selected',
                ];
            }
        }

        return $results;
    }
}
