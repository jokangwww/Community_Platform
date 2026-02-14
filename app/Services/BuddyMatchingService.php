<?php

namespace App\Services;

use App\Models\BuddyMatch;
use App\Models\BuddyParticipant;
use App\Models\BuddySetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuddyMatchingService
{
    /**
     * Maximum mentees per mentor (can be made configurable)
     */
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

            // Get available mentors (active, with capacity)
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
        return BuddyParticipant::mentors()
            ->where('status', 'active')
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

        $query = BuddyParticipant::mentees()
            ->where('status', 'active')
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
            // Simple first-come, first-served
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
        // Get mentee's subject
        $menteeSubjectId = $mentee->subject_id;

        if (!$menteeSubjectId) {
            return null;
        }

        // Find a mentor with the same subject and available capacity
        foreach ($mentors as $mentorId => $mentor) {
            // Check if mentor has capacity
            if ($mentor->mentor_matches_count >= $this->maxMenteesPerMentor) {
                continue;
            }

            // Check if mentor has the same subject
            if ($mentor->subject_id === $menteeSubjectId) {
                // Create the match
                $match = BuddyMatch::create([
                    'mentor_id' => $mentor->id,
                    'mentee_id' => $mentee->id,
                    'subject_id' => $menteeSubjectId,
                    'matched_date' => now(),
                    'status' => 'active',
                    'total_sessions' => 0,
                    'completed_sessions' => 0,
                ]);

                // Update mentor's match count in memory
                $mentor->mentor_matches_count++;

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

        return BuddyMatch::create([
            'mentor_id' => $mentorId,
            'mentee_id' => $menteeId,
            'subject_id' => $finalSubjectId,
            'matched_date' => now(),
            'status' => 'active',
            'total_sessions' => 0,
            'completed_sessions' => 0,
        ]);
    }

    /**
     * Get matching statistics
     * 
     * @return array
     */
    public function getMatchingStats(): array
    {
        $totalMentors = BuddyParticipant::mentors()->where('status', 'active')->count();
        $totalMentees = BuddyParticipant::mentees()->where('status', 'active')->count();
        
        $matchedMentees = BuddyParticipant::mentees()
            ->where('status', 'active')
            ->whereHas('menteeMatches', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        $unmatchedMentees = $totalMentees - $matchedMentees;

        $availableMentorSlots = BuddyParticipant::mentors()
            ->where('status', 'active')
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
            'active_matches' => BuddyMatch::where('status', 'active')->count(),
        ];
    }

    /**
     * Preview what matches would be created without actually creating them
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
