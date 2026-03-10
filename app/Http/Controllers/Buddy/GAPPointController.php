<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Models\BuddyParticipant;
use App\Models\BuddyMatch;
use App\Models\BuddySession;
use App\Models\BuddySemesterSetting;
use Illuminate\Http\Request;

class GAPPointController extends Controller
{
    /**
     * Get attendance data for GAP point eligibility
     */
    public function index(Request $request)
    {
        try {
            // Resolve target semester
            $semesterId = $request->query('semester_id');
            $targetSemester = $semesterId
                ? BuddySemesterSetting::find($semesterId)
                : BuddySemesterSetting::getActiveSemester();

            $query = BuddyParticipant::with(['user', 'subject'])
                ->where('status', 'active');

            if ($targetSemester) {
                $query->where('semester_id', $targetSemester->id);
            }

            $participants = $query->get();

            $students = [];
            $totalAttendance = 0;
            $eligibleCount = 0;

            foreach ($participants as $participant) {
                // Find match for this participant
                $match = BuddyMatch::where(function ($query) use ($participant) {
                    $query->where('mentor_id', $participant->id)
                          ->orWhere('mentee_id', $participant->id);
                })->first();

                if (!$match) {
                    continue;
                }

                // Determine role
                $role = $match->mentor_id === $participant->id ? 'mentor' : 'mentee';

                // Get sessions for this match
                $totalSessions = BuddySession::where('match_id', $match->id)->count();
                
                // Get attended sessions - check if participant checked in
                $checkInColumn = $role === 'mentor' ? 'mentor_check_in' : 'mentee_check_in';
                $attendedSessions = BuddySession::where('match_id', $match->id)
                    ->whereNotNull($checkInColumn)
                    ->count();

                // Calculate attendance rate
                $attendanceRate = $totalSessions > 0 
                    ? round(($attendedSessions / $totalSessions) * 100, 1) 
                    : 0;

                // GAP eligibility requires >= 80% attendance
                $isEligible = $attendanceRate >= 80;

                if ($isEligible) {
                    $eligibleCount++;
                }

                $totalAttendance += $attendanceRate;

                $students[] = [
                    'id' => (string) $participant->id,
                    'name' => $participant->full_name ?? $participant->user->name ?? 'Unknown',
                    'studentId' => $participant->student_id ?? $participant->user->student_id ?? 'N/A',
                    'role' => $role,
                    'faculty' => $participant->faculty ?? 'N/A',
                    'programme' => $participant->course ?? 'N/A',
                    'totalSessions' => $totalSessions,
                    'attendedSessions' => $attendedSessions,
                    'attendanceRate' => $attendanceRate,
                    'isEligible' => $isEligible,
                ];
            }

            $totalStudents = count($students);
            $avgAttendance = $totalStudents > 0 
                ? round($totalAttendance / $totalStudents, 1) 
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'students' => $students,
                    'stats' => [
                        'totalStudents' => $totalStudents,
                        'eligibleCount' => $eligibleCount,
                        'notEligibleCount' => $totalStudents - $eligibleCount,
                        'avgAttendance' => $avgAttendance,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export GAP point eligibility report as CSV
     */
    public function export()
    {
        try {
            // Resolve target semester from query
            $semesterId = request()->query('semester_id');
            $targetSemester = $semesterId
                ? BuddySemesterSetting::find($semesterId)
                : BuddySemesterSetting::getActiveSemester();

            $query = BuddyParticipant::with(['user', 'subject'])
                ->where('status', 'active');

            if ($targetSemester) {
                $query->where('semester_id', $targetSemester->id);
            }

            $participants = $query->get();

            $csv = "Name,Student ID,Role,Faculty,Programme,Total Sessions,Attended Sessions,Attendance Rate,GAP Eligible\n";

            foreach ($participants as $participant) {
                $match = BuddyMatch::where(function ($query) use ($participant) {
                    $query->where('mentor_id', $participant->id)
                          ->orWhere('mentee_id', $participant->id);
                })->first();

                if (!$match) {
                    continue;
                }

                $role = $match->mentor_id === $participant->id ? 'mentor' : 'mentee';
                $checkInColumn = $role === 'mentor' ? 'mentor_check_in' : 'mentee_check_in';

                $totalSessions = BuddySession::where('match_id', $match->id)->count();
                $attendedSessions = BuddySession::where('match_id', $match->id)
                    ->whereNotNull($checkInColumn)
                    ->count();

                $attendanceRate = $totalSessions > 0 
                    ? round(($attendedSessions / $totalSessions) * 100, 1) 
                    : 0;

                $isEligible = $attendanceRate >= 80 ? 'Yes' : 'No';

                $name = str_replace(',', ' ', $participant->full_name ?? $participant->user->name ?? 'Unknown');
                $faculty = str_replace(',', ' ', $participant->faculty ?? 'N/A');
                $programme = str_replace(',', ' ', $participant->course ?? 'N/A');

                $csv .= implode(',', [
                    $name,
                    $participant->student_id ?? $participant->user->student_id ?? 'N/A',
                    $role,
                    $faculty,
                    $programme,
                    $totalSessions,
                    $attendedSessions,
                    $attendanceRate . '%',
                    $isEligible,
                ]) . "\n";
            }

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="gap_point_eligibility_' . date('Y-m-d') . '.csv"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report: ' . $e->getMessage()
            ], 500);
        }
    }
}
