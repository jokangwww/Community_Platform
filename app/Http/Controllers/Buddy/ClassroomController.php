<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Models\BuddyAssignment;
use App\Models\BuddyAssignmentSubmission;
use App\Models\BuddyQuiz;
use App\Models\BuddyQuizAttempt;
use App\Models\BuddyQuizQuestion;
use App\Models\BuddyStudyMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ClassroomController extends Controller
{
    /**
     * Extend execution time for classroom operations.
     * The PHP dev server on Windows can be slow due to antivirus scanning during autoloading.
     */
    public function __construct()
    {
        set_time_limit(120);
    }

    /**
     * Get the current user's participant record from request (set by middleware)
     */
    private function getParticipant(Request $request)
    {
        return $request->attributes->get('participant');
    }

    /**
     * Get the match from request (set by middleware)
     */
    private function getMatch(Request $request)
    {
        return $request->attributes->get('match');
    }

    /**
     * Get all active match IDs for the mentor who owns the given match.
     * Used to replicate/aggregate classroom content across all mentor's mentee matches.
     */
    private function getMentorMatchIds(int $matchId): array
    {
        $mentorPivot = DB::table('buddy_match_participants')
            ->where('match_id', $matchId)
            ->where('role', 'mentor')
            ->first();

        if (!$mentorPivot) return [$matchId];

        $match = \App\Models\BuddyMatch::find($matchId);
        if (!$match) return [$matchId];

        return \App\Models\BuddyMatch::whereHas('participants', function ($q) use ($mentorPivot) {
            $q->where('buddy_match_participants.participant_id', $mentorPivot->participant_id)
              ->where('buddy_match_participants.role', 'mentor');
        })
        ->where('status', 'active')
        ->where('semester_id', $match->semester_id)
        ->pluck('id')
        ->toArray();
    }

    /**
     * Auto-sync classroom content to a mentee's match from sibling matches (same mentor).
     * This ensures all mentees see the same materials/quizzes/assignments regardless of
     * which match the mentor used when creating content.
     */
    private function syncClassroomContent(int $matchId): void
    {
        $allMatchIds = $this->getMentorMatchIds($matchId);
        $siblingMatchIds = array_diff($allMatchIds, [$matchId]);

        if (empty($siblingMatchIds)) return;

        // Sync study materials (match by name to avoid duplicates)
        $existingMaterials = BuddyStudyMaterial::where('match_id', $matchId)->get()->keyBy('name');
        $siblingMaterials = BuddyStudyMaterial::whereIn('match_id', $siblingMatchIds)
            ->get()
            ->unique('name');

        foreach ($siblingMaterials as $mat) {
            if ($existingMaterials->has($mat->name)) {
                // Update existing record if content changed
                $existing = $existingMaterials->get($mat->name);
                if ($existing->description !== $mat->description || $existing->file_path !== $mat->file_path) {
                    $existing->update([
                        'description' => $mat->description,
                        'file_name' => $mat->file_name,
                        'file_path' => $mat->file_path,
                        'file_size' => $mat->file_size,
                        'mime_type' => $mat->mime_type,
                    ]);
                }
            } else {
                BuddyStudyMaterial::create([
                    'match_id' => $matchId,
                    'uploaded_by' => $mat->uploaded_by,
                    'name' => $mat->name,
                    'description' => $mat->description,
                    'file_name' => $mat->file_name,
                    'file_path' => $mat->file_path,
                    'file_size' => $mat->file_size,
                    'mime_type' => $mat->mime_type,
                ]);
            }
        }

        // Sync quizzes (match by title)
        $existingQuizzes = BuddyQuiz::where('match_id', $matchId)->get()->keyBy('title');
        $siblingQuizzes = BuddyQuiz::whereIn('match_id', $siblingMatchIds)
            ->with('questions')
            ->get()
            ->unique('title');

        foreach ($siblingQuizzes as $quiz) {
            if ($existingQuizzes->has($quiz->title)) {
                // Update existing quiz if content changed
                $existing = $existingQuizzes->get($quiz->title);
                $existing->update([
                    'total_marks' => $quiz->total_marks,
                    'due_date' => $quiz->due_date,
                    'status' => $quiz->status,
                ]);
            } else {
                $newQuiz = BuddyQuiz::create([
                    'match_id' => $matchId,
                    'created_by' => $quiz->created_by,
                    'title' => $quiz->title,
                    'total_marks' => $quiz->total_marks,
                    'due_date' => $quiz->due_date,
                    'status' => $quiz->status,
                ]);

                foreach ($quiz->questions as $q) {
                    BuddyQuizQuestion::create([
                        'quiz_id' => $newQuiz->id,
                        'question' => $q->question,
                        'options' => $q->options,
                        'correct_answer' => $q->correct_answer,
                        'order' => $q->order,
                    ]);
                }
            }
        }

        // Sync assignments (match by title)
        $existingAssignments = BuddyAssignment::where('match_id', $matchId)->get()->keyBy('title');
        $siblingAssignments = BuddyAssignment::whereIn('match_id', $siblingMatchIds)
            ->get()
            ->unique('title');

        foreach ($siblingAssignments as $assignment) {
            if ($existingAssignments->has($assignment->title)) {
                // Update existing assignment if content changed
                $existing = $existingAssignments->get($assignment->title);
                $existing->update([
                    'description' => $assignment->description,
                    'due_date' => $assignment->due_date,
                    'total_marks' => $assignment->total_marks,
                    'attachments' => $assignment->attachments,
                ]);
            } else {
                BuddyAssignment::create([
                    'match_id' => $matchId,
                    'created_by' => $assignment->created_by,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'due_date' => $assignment->due_date,
                    'total_marks' => $assignment->total_marks,
                    'attachments' => $assignment->attachments,
                ]);
            }
        }
    }

    /**
     * Get classroom data for a match (materials, quizzes, assignments)
     */
    public function getClassroomData(Request $request, int $matchId): JsonResponse
    {
        // Get data from middleware
        $participant = $this->getParticipant($request);
        $match = $this->getMatch($request);
        $isMentor = $request->attributes->get('isMentor');

        // For mentees: auto-sync content from mentor's other matches
        if (!$isMentor) {
            $this->syncClassroomContent($matchId);
        }

        // Get materials
        $materials = BuddyStudyMaterial::where('match_id', $matchId)
            ->with('uploader:id,full_name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($material) {
                return [
                    'id' => (string) $material->id,
                    'name' => $material->name,
                    'description' => $material->description,
                    'fileName' => $material->file_name,
                    'fileSize' => $material->file_size,
                    'uploadedDate' => $material->created_at->format('Y-m-d'),
                    'uploadedBy' => $material->uploader->full_name,
                ];
            });

        // Get quizzes with questions (hide correct answers for mentees)
        $quizzes = BuddyQuiz::where('match_id', $matchId)
            ->with(['questions', 'attempts' => function ($query) use ($participant, $isMentor) {
                if ($isMentor) {
                    // Load all attempts for mentor so we can report attemptsCount
                    $query->latest('completed_at');
                } else {
                    // For mentee, only load their own attempt
                    $query->where('participant_id', $participant->id);
                }
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($quiz) use ($isMentor, $participant) {
                // Mentee: check their own attempt; Mentor: no personal attempt
                $attempt = $isMentor ? null : $quiz->attempts->first();
                $hasAttempted = $attempt !== null;

                $data = [
                    'id' => (string) $quiz->id,
                    'title' => $quiz->title,
                    'totalMarks' => $quiz->total_marks,
                    'dueDate' => $quiz->due_date?->format('Y-m-d') ?? '',
                    'status' => $quiz->status,
                    'createdDate' => $quiz->created_at->format('Y-m-d'),
                    'hasAttempted' => $hasAttempted,
                    'attempt' => $hasAttempted ? [
                        'quizId' => (string) $quiz->id,
                        'score' => $attempt->score,
                        'totalMarks' => $attempt->total_marks,
                        'completedDate' => $attempt->completed_at->format('Y-m-d'),
                        'answers' => $attempt->answers,
                    ] : null,
                    'questions' => $quiz->questions->map(function ($q) use ($isMentor, $hasAttempted) {
                        return [
                            'id' => (string) $q->id,
                            'question' => $q->question,
                            'options' => $q->options,
                            // Include correct answer for mentors OR if mentee has already attempted
                            'correctAnswer' => ($isMentor || $hasAttempted) ? $q->correct_answer : null,
                        ];
                    })->toArray(),
                ];

                // For mentor: include attempt statistics for activity feed
                if ($isMentor) {
                    $latestAttempt = $quiz->attempts->first();
                    $data['attemptsCount'] = $quiz->attempts->count();
                    $data['latestAttemptAt'] = $latestAttempt
                        ? $latestAttempt->completed_at->format('Y-m-d')
                        : null;
                }

                return $data;
            });

        // Get assignments
        $assignments = BuddyAssignment::where('match_id', $matchId)
            ->with(['submissions' => function ($query) use ($participant, $isMentor) {
                if ($isMentor) {
                    // For mentors, load all submissions ordered by latest to get count & latest date
                    $query->latest('submitted_at');
                } else {
                    $query->where('participant_id', $participant->id);
                }
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($assignment) use ($isMentor) {
                $submission = $assignment->submissions->first();

                $data = [
                    'id' => (string) $assignment->id,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'dueDate' => $assignment->due_date->format('Y-m-d'),
                    'totalMarks' => $assignment->total_marks,
                    'attachments' => collect($assignment->attachments ?? [])->map(function ($attachment) {
                        // Handle both string format and object format {name, path}
                        return is_array($attachment) ? ($attachment['name'] ?? '') : $attachment;
                    })->filter()->values()->toArray(),
                    'createdDate' => $assignment->created_at->format('Y-m-d'),
                    'hasSubmitted' => $submission !== null,
                    'submission' => $submission ? [
                        'id' => (string) $submission->id,
                        'fileName' => $submission->file_name,
                        'submittedDate' => $submission->submitted_at->format('Y-m-d'),
                        'status' => $submission->status,
                        'marks' => $submission->marks,
                        'feedback' => $submission->feedback,
                    ] : null,
                ];

                // Include submission statistics for mentor view
                if ($isMentor) {
                    $data['submissionsCount'] = $assignment->submissions->count();
                    $data['latestSubmissionAt'] = $submission
                        ? $submission->submitted_at->format('Y-m-d')
                        : null;
                }

                return $data;
            });

        // Get mentees for this mentor (across ALL matches for complete view)
        $mentees = [];
        if ($isMentor) {
            $allMatchIds = $this->getMentorMatchIds($matchId);
            $allMenteeParticipantIds = DB::table('buddy_match_participants')
                ->whereIn('match_id', $allMatchIds)
                ->where('role', 'mentee')
                ->pluck('participant_id')
                ->unique();

            $menteeParticipants = \App\Models\BuddyParticipant::whereIn('id', $allMenteeParticipantIds)->get();

            foreach ($menteeParticipants as $menteeParticipant) {
                $mentees[] = [
                    'id' => (string) $menteeParticipant->id,
                    'name' => $menteeParticipant->full_name,
                    'studentId' => $menteeParticipant->student_id,
                ];
            }

            // Aggregate quiz attempts & assignment submissions from sibling matches
            $siblingMatchIds = array_diff($allMatchIds, [$matchId]);
            if (!empty($siblingMatchIds)) {
                $quizzes = $quizzes->map(function ($quizData) use ($siblingMatchIds) {
                    $siblingQuizIds = BuddyQuiz::whereIn('match_id', $siblingMatchIds)
                        ->where('title', $quizData['title'])
                        ->pluck('id');

                    if ($siblingQuizIds->isNotEmpty()) {
                        $siblingAttempts = BuddyQuizAttempt::whereIn('quiz_id', $siblingQuizIds)->count();
                        $quizData['attemptsCount'] = ($quizData['attemptsCount'] ?? 0) + $siblingAttempts;

                        if (empty($quizData['latestAttemptAt'])) {
                            $latest = BuddyQuizAttempt::whereIn('quiz_id', $siblingQuizIds)
                                ->latest('completed_at')
                                ->first();
                            if ($latest) {
                                $quizData['latestAttemptAt'] = $latest->completed_at->format('Y-m-d');
                            }
                        }
                    }
                    return $quizData;
                });

                $assignments = $assignments->map(function ($assignmentData) use ($siblingMatchIds) {
                    $siblingAssignmentIds = BuddyAssignment::whereIn('match_id', $siblingMatchIds)
                        ->where('title', $assignmentData['title'])
                        ->pluck('id');

                    if ($siblingAssignmentIds->isNotEmpty()) {
                        $siblingSubmissions = BuddyAssignmentSubmission::whereIn('assignment_id', $siblingAssignmentIds)->count();
                        $assignmentData['submissionsCount'] = ($assignmentData['submissionsCount'] ?? 0) + $siblingSubmissions;

                        if (empty($assignmentData['latestSubmissionAt'])) {
                            $latest = BuddyAssignmentSubmission::whereIn('assignment_id', $siblingAssignmentIds)
                                ->latest('submitted_at')
                                ->first();
                            if ($latest) {
                                $assignmentData['latestSubmissionAt'] = $latest->submitted_at->format('Y-m-d');
                            }
                        }
                    }
                    return $assignmentData;
                });
            }
        }

        return response()->json([
            'materials' => $materials,
            'quizzes' => $quizzes,
            'assignments' => $assignments,
            'mentees' => $mentees,
            'userRole' => $isMentor ? 'mentor' : 'mentee',
            'is_readonly' => (bool) $request->attributes->get('readonly'),
        ]);
    }

    // ==================== STUDY MATERIALS ====================

    /**
     * Upload a study material
     */
    public function uploadMaterial(Request $request, int $matchId): JsonResponse
    {
        // Get data from middleware
        $participant = $this->getParticipant($request);
        $isMentor = $request->attributes->get('isMentor');
        
        // Only mentor can upload materials
        if (!$isMentor) {
            return response()->json(['error' => 'Only mentors can upload materials'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store('buddy/materials/' . $matchId, 'public');
        
        $material = BuddyStudyMaterial::create([
            'match_id' => $matchId,
            'uploaded_by' => $participant->id,
            'name' => $request->name,
            'description' => $request->description,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $this->formatFileSize($file->getSize()),
            'mime_type' => $file->getMimeType(),
        ]);

        // Replicate material to all mentor's other matches
        $siblingMatchIds = array_diff($this->getMentorMatchIds($matchId), [$matchId]);
        foreach ($siblingMatchIds as $siblingId) {
            BuddyStudyMaterial::create([
                'match_id' => $siblingId,
                'uploaded_by' => $participant->id,
                'name' => $request->name,
                'description' => $request->description,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path, // share the same file
                'file_size' => $this->formatFileSize($file->getSize()),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        return response()->json([
            'message' => 'Material uploaded successfully',
            'material' => [
                'id' => (string) $material->id,
                'name' => $material->name,
                'description' => $material->description,
                'fileName' => $material->file_name,
                'fileSize' => $material->file_size,
                'uploadedDate' => $material->created_at->format('Y-m-d'),
                'uploadedBy' => $participant->full_name,
            ],
        ]);
    }

    /**
     * Update a study material
     */
    public function updateMaterial(Request $request, int $matchId, int $materialId): JsonResponse
    {
        // Get data from middleware
        $participant = $this->getParticipant($request);
        $isMentor = $request->attributes->get('isMentor');
        
        // Only mentor can update materials
        if (!$isMentor) {
            return response()->json(['error' => 'Only mentors can update materials'], 403);
        }

        $material = BuddyStudyMaterial::where('match_id', $matchId)
            ->where('id', $materialId)
            ->first();

        if (!$material) {
            return response()->json(['error' => 'Material not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip|max:10240',
        ]);

        // Update basic fields
        $originalName = $material->name;

        $material->name = $request->name;
        $material->description = $request->description;

        // If new file uploaded, replace the old one
        if ($request->hasFile('file')) {
            // Delete old file
            Storage::disk('public')->delete($material->file_path);
            
            // Upload new file
            $file = $request->file('file');
            $path = $file->store('buddy/materials/' . $matchId, 'public');
            
            $material->file_name = $file->getClientOriginalName();
            $material->file_path = $path;
            $material->file_size = $this->formatFileSize($file->getSize());
            $material->mime_type = $file->getMimeType();
        }

        $material->save();

        // Propagate changes to sibling matches
        $siblingMatchIds = array_diff($this->getMentorMatchIds($matchId), [$matchId]);
        if (!empty($siblingMatchIds)) {
            $siblingMaterials = BuddyStudyMaterial::whereIn('match_id', $siblingMatchIds)
                ->where('name', $originalName)
                ->get();
            foreach ($siblingMaterials as $siblingMat) {
                $siblingMat->name = $material->name;
                $siblingMat->description = $material->description;
                if ($request->hasFile('file')) {
                    $siblingMat->file_name = $material->file_name;
                    $siblingMat->file_path = $material->file_path;
                    $siblingMat->file_size = $material->file_size;
                    $siblingMat->mime_type = $material->mime_type;
                }
                $siblingMat->save();
            }
        }

        return response()->json([
            'message' => 'Material updated successfully',
            'material' => [
                'id' => (string) $material->id,
                'name' => $material->name,
                'description' => $material->description,
                'fileName' => $material->file_name,
                'fileSize' => $material->file_size,
                'uploadedDate' => $material->created_at->format('Y-m-d'),
                'uploadedBy' => $participant->full_name,
            ],
        ]);
    }

    /**
     * Delete a study material
     */
    public function deleteMaterial(Request $request, int $matchId, int $materialId): JsonResponse
    {
        // Get data from middleware
        $isMentor = $request->attributes->get('isMentor');
        
        // Only mentor can delete materials
        if (!$isMentor) {
            return response()->json(['error' => 'Only mentors can delete materials'], 403);
        }

        $material = BuddyStudyMaterial::where('match_id', $matchId)
            ->where('id', $materialId)
            ->first();

        if (!$material) {
            return response()->json(['error' => 'Material not found'], 404);
        }

        // Delete file from storage
        Storage::disk('public')->delete($material->file_path);
        
        // Also delete copies from sibling matches (same file_path)
        $siblingMatchIds = array_diff($this->getMentorMatchIds($matchId), [$matchId]);
        if (!empty($siblingMatchIds)) {
            BuddyStudyMaterial::whereIn('match_id', $siblingMatchIds)
                ->where('file_path', $material->file_path)
                ->delete();
        }

        $material->delete();

        return response()->json(['message' => 'Material deleted successfully']);
    }

    /**
     * Download a study material
     */
    public function downloadMaterial(Request $request, int $matchId, int $materialId): mixed
    {
        // Match access already validated by middleware

        $material = BuddyStudyMaterial::where('match_id', $matchId)
            ->where('id', $materialId)
            ->first();

        if (!$material || !$material->file_path) {
            return response()->json(['error' => 'Material not found'], 404);
        }

        return Storage::disk('public')->download($material->file_path, $material->file_name ?? 'download');
    }

    // ==================== QUIZZES ====================

    /**
     * Create a new quiz
     */
    public function createQuiz(Request $request, int $matchId): JsonResponse
    {
        // Get data from middleware
        $participant = $this->getParticipant($request);
        $isMentor = $request->attributes->get('isMentor');
        
        // Only mentor can create quizzes
        if (!$isMentor) {
            return response()->json(['error' => 'Only mentors can create quizzes'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'totalMarks' => 'required|integer|min:1',
            'dueDate' => 'nullable|date',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.correctAnswer' => 'required|integer|min:0',
        ]);

        $quiz = BuddyQuiz::create([
            'match_id' => $matchId,
            'created_by' => $participant->id,
            'title' => $request->title,
            'total_marks' => $request->totalMarks,
            'due_date' => $request->dueDate,
            'status' => 'open',
        ]);

        // Create questions
        foreach ($request->questions as $index => $questionData) {
            BuddyQuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question' => $questionData['question'],
                'options' => $questionData['options'],
                'correct_answer' => $questionData['correctAnswer'],
                'order' => $index,
            ]);
        }

        // Replicate quiz to all mentor's other matches
        $siblingMatchIds = array_diff($this->getMentorMatchIds($matchId), [$matchId]);
        foreach ($siblingMatchIds as $siblingId) {
            $siblingQuiz = BuddyQuiz::create([
                'match_id' => $siblingId,
                'created_by' => $participant->id,
                'title' => $request->title,
                'total_marks' => $request->totalMarks,
                'due_date' => $request->dueDate,
                'status' => 'open',
            ]);

            foreach ($request->questions as $index => $questionData) {
                BuddyQuizQuestion::create([
                    'quiz_id' => $siblingQuiz->id,
                    'question' => $questionData['question'],
                    'options' => $questionData['options'],
                    'correct_answer' => $questionData['correctAnswer'],
                    'order' => $index,
                ]);
            }
        }

        $quiz->load('questions');

        return response()->json([
            'message' => 'Quiz created successfully',
            'quiz' => [
                'id' => (string) $quiz->id,
                'title' => $quiz->title,
                'totalMarks' => $quiz->total_marks,
                'dueDate' => $quiz->due_date?->format('Y-m-d') ?? '',
                'status' => $quiz->status,
                'createdDate' => $quiz->created_at->format('Y-m-d'),
                'hasAttempted' => false,
                'questions' => $quiz->questions->map(function ($q) {
                    return [
                        'id' => (string) $q->id,
                        'question' => $q->question,
                        'options' => $q->options,
                        'correctAnswer' => $q->correct_answer,
                    ];
                })->toArray(),
            ],
        ]);
    }

    /**
     * Update a quiz
     */
    public function updateQuiz(Request $request, int $matchId, int $quizId): JsonResponse
    {
        // Get data from middleware
        $isMentor = $request->attributes->get('isMentor');
        
        // Only mentor can update quizzes
        if (!$isMentor) {
            return response()->json(['error' => 'Only mentors can update quizzes'], 403);
        }

        $quiz = BuddyQuiz::where('match_id', $matchId)
            ->where('id', $quizId)
            ->first();

        if (!$quiz) {
            return response()->json(['error' => 'Quiz not found'], 404);
        }

        // Check if any mentee has attempted this quiz (including sibling quizzes)
        $hasAttempts = BuddyQuizAttempt::where('quiz_id', $quizId)->exists();
        if (!$hasAttempts) {
            $siblingQuizIds = BuddyQuiz::whereIn('match_id', $this->getMentorMatchIds($matchId))
                ->where('title', $quiz->title)
                ->where('id', '!=', $quizId)
                ->pluck('id');
            if ($siblingQuizIds->isNotEmpty()) {
                $hasAttempts = BuddyQuizAttempt::whereIn('quiz_id', $siblingQuizIds)->exists();
            }
        }

        if ($hasAttempts) {
            // Only allow updating title and due date when mentees have attempted
            $request->validate([
                'title' => 'required|string|max:255',
                'dueDate' => 'nullable|date',
            ]);

            // Capture original title BEFORE update (Laravel syncs originals after save)
            $originalTitle = $quiz->title;

            $quiz->update([
                'title' => $request->title,
                'due_date' => $request->dueDate,
                'status' => $request->status ?? $quiz->status,
            ]);

            // Also update sibling quizzes title and due date
            $siblingMatchIds = array_diff($this->getMentorMatchIds($matchId), [$matchId]);
            if (!empty($siblingMatchIds)) {
                BuddyQuiz::whereIn('match_id', $siblingMatchIds)
                    ->where('title', $originalTitle)
                    ->update([
                        'title' => $request->title,
                        'due_date' => $request->dueDate,
                    ]);
            }
        } else {
            // Full update allowed when no attempts exist
            $request->validate([
                'title' => 'required|string|max:255',
                'totalMarks' => 'required|integer|min:1',
                'dueDate' => 'nullable|date',
                'status' => 'nullable|in:open,closed',
                'questions' => 'required|array|min:1',
                'questions.*.question' => 'required|string',
                'questions.*.options' => 'required|array|min:2',
                'questions.*.correctAnswer' => 'required|integer|min:0',
            ]);

            $originalTitle = $quiz->title;

            $quiz->update([
                'title' => $request->title,
                'total_marks' => $request->totalMarks,
                'due_date' => $request->dueDate,
                'status' => $request->status ?? $quiz->status,
            ]);

            // Delete existing questions and create new ones
            BuddyQuizQuestion::where('quiz_id', $quizId)->delete();
            
            foreach ($request->questions as $index => $questionData) {
                BuddyQuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question' => $questionData['question'],
                    'options' => $questionData['options'],
                    'correct_answer' => $questionData['correctAnswer'],
                    'order' => $index,
                ]);
            }

            // Propagate full update to sibling quizzes
            $siblingMatchIds = array_diff($this->getMentorMatchIds($matchId), [$matchId]);
            if (!empty($siblingMatchIds)) {
                $siblingQuizzes = BuddyQuiz::whereIn('match_id', $siblingMatchIds)
                    ->where('title', $originalTitle)
                    ->get();
                foreach ($siblingQuizzes as $siblingQuiz) {
                    $siblingQuiz->update([
                        'title' => $request->title,
                        'total_marks' => $request->totalMarks,
                        'due_date' => $request->dueDate,
                        'status' => $request->status ?? $siblingQuiz->status,
                    ]);

                    // Replace sibling questions with the same updated questions
                    BuddyQuizQuestion::where('quiz_id', $siblingQuiz->id)->delete();
                    foreach ($request->questions as $index => $questionData) {
                        BuddyQuizQuestion::create([
                            'quiz_id' => $siblingQuiz->id,
                            'question' => $questionData['question'],
                            'options' => $questionData['options'],
                            'correct_answer' => $questionData['correctAnswer'],
                            'order' => $index,
                        ]);
                    }
                }
            }
        }

        $quiz->load('questions');

        return response()->json([
            'message' => 'Quiz updated successfully',
            'quiz' => [
                'id' => (string) $quiz->id,
                'title' => $quiz->title,
                'totalMarks' => $quiz->total_marks,
                'dueDate' => $quiz->due_date?->format('Y-m-d') ?? '',
                'status' => $quiz->status,
                'createdDate' => $quiz->created_at->format('Y-m-d'),
                'hasAttempted' => false,
                'questions' => $quiz->questions->map(function ($q) {
                    return [
                        'id' => (string) $q->id,
                        'question' => $q->question,
                        'options' => $q->options,
                        'correctAnswer' => $q->correct_answer,
                    ];
                })->toArray(),
            ],
        ]);
    }

    /**
     * Submit quiz attempt
     */
    public function submitQuiz(Request $request, int $matchId, int $quizId): JsonResponse
    {
        // Get data from middleware
        $participant = $this->getParticipant($request);
        $isMentee = $request->attributes->get('isMentee');
        
        // Only mentee can submit quiz
        if (!$isMentee) {
            return response()->json(['error' => 'Only mentees can submit quizzes'], 403);
        }

        $quiz = BuddyQuiz::where('match_id', $matchId)
            ->where('id', $quizId)
            ->with('questions')
            ->first();

        if (!$quiz) {
            return response()->json(['error' => 'Quiz not found'], 404);
        }

        if ($quiz->status !== 'open') {
            return response()->json(['error' => 'Quiz is closed'], 400);
        }

        // Check if already attempted
        $existingAttempt = BuddyQuizAttempt::where('quiz_id', $quizId)
            ->where('participant_id', $participant->id)
            ->first();

        if ($existingAttempt) {
            return response()->json(['error' => 'You have already attempted this quiz'], 400);
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'required|integer|min:0',
        ]);

        // Calculate score
        $score = 0;
        $marksPerQuestion = $quiz->total_marks / $quiz->questions->count();
        
        foreach ($quiz->questions as $index => $question) {
            if (isset($request->answers[$index]) && $request->answers[$index] === $question->correct_answer) {
                $score += $marksPerQuestion;
            }
        }

        $attempt = BuddyQuizAttempt::create([
            'quiz_id' => $quizId,
            'participant_id' => $participant->id,
            'score' => round($score),
            'total_marks' => $quiz->total_marks,
            'answers' => $request->answers,
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Quiz submitted successfully',
            'result' => [
                'quizId' => (string) $quizId,
                'score' => $attempt->score,
                'totalMarks' => $attempt->total_marks,
                'completedDate' => $attempt->completed_at->format('Y-m-d'),
                'answers' => $attempt->answers,
            ],
            'questions' => $quiz->questions->map(function ($q) {
                return [
                    'id' => (string) $q->id,
                    'question' => $q->question,
                    'options' => $q->options,
                    'correctAnswer' => $q->correct_answer,
                ];
            })->toArray(),
        ]);
    }

    /**
     * Get quiz results (for mentors)
     */
    public function getQuizResults(Request $request, int $matchId, int $quizId): JsonResponse
    {
        // Get data from middleware
        $isMentor = $request->attributes->get('isMentor');
        
        // Only mentor can view all results
        if (!$isMentor) {
            return response()->json(['error' => 'Only mentors can view all results'], 403);
        }

        $quiz = BuddyQuiz::where('match_id', $matchId)
            ->where('id', $quizId)
            ->first();

        if (!$quiz) {
            return response()->json(['error' => 'Quiz not found'], 404);
        }

        // Aggregate attempts across all mentor's matches (sibling quizzes with same title)
        $allMatchIds = $this->getMentorMatchIds($matchId);
        $siblingQuizIds = BuddyQuiz::whereIn('match_id', $allMatchIds)
            ->where('title', $quiz->title)
            ->pluck('id');

        $attempts = BuddyQuizAttempt::whereIn('quiz_id', $siblingQuizIds)
            ->with('participant')
            ->get()
            ->map(function ($attempt) {
                return [
                    'quizId' => (string) $attempt->quiz_id,
                    'studentName' => $attempt->participant->full_name,
                    'studentId' => $attempt->participant->student_id,
                    'score' => $attempt->score,
                    'totalMarks' => $attempt->total_marks,
                    'completedDate' => $attempt->completed_at->format('Y-m-d'),
                    'answers' => $attempt->answers,
                ];
            });

        return response()->json([
            'quiz' => [
                'id' => (string) $quiz->id,
                'title' => $quiz->title,
                'totalMarks' => $quiz->total_marks,
            ],
            'attempts' => $attempts,
        ]);
    }

    // ==================== ASSIGNMENTS ====================

    /**
     * Create a new assignment
     */
    public function createAssignment(Request $request, int $matchId): JsonResponse
    {
        // Get data from middleware
        $participant = $this->getParticipant($request);
        $isMentor = $request->attributes->get('isMentor');
        
        // Only mentor can create assignments
        if (!$isMentor) {
            return response()->json(['error' => 'Only mentors can create assignments'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'dueDate' => 'required|date',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip|max:10240',
        ]);

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('buddy/assignments/' . $matchId, 'public');
                $attachmentPaths[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                ];
            }
        }

        $assignment = BuddyAssignment::create([
            'match_id' => $matchId,
            'created_by' => $participant->id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->dueDate,
            'total_marks' => 100, // Default value
            'attachments' => $attachmentPaths,
        ]);

        // Replicate assignment to all mentor's other matches
        $siblingMatchIds = array_diff($this->getMentorMatchIds($matchId), [$matchId]);
        foreach ($siblingMatchIds as $siblingId) {
            BuddyAssignment::create([
                'match_id' => $siblingId,
                'created_by' => $participant->id,
                'title' => $request->title,
                'description' => $request->description,
                'due_date' => $request->dueDate,
                'total_marks' => 100,
                'attachments' => $attachmentPaths, // share same file paths
            ]);
        }

        return response()->json([
            'message' => 'Assignment created successfully',
            'assignment' => [
                'id' => (string) $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'dueDate' => $assignment->due_date->format('Y-m-d'),
                'totalMarks' => $assignment->total_marks,
                'attachments' => collect($attachmentPaths)->pluck('name')->toArray(),
                'createdDate' => $assignment->created_at->format('Y-m-d'),
                'hasSubmitted' => false,
                'submission' => null,
            ],
        ]);
    }

    /**
     * Update an assignment
     */
    public function updateAssignment(Request $request, int $matchId, int $assignmentId): JsonResponse
    {
        // Get data from middleware
        $isMentor = $request->attributes->get('isMentor');
        
        // Only mentor can update assignments
        if (!$isMentor) {
            return response()->json(['error' => 'Only mentors can update assignments'], 403);
        }

        $assignment = BuddyAssignment::where('match_id', $matchId)
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'dueDate' => 'required|date',
            'totalMarks' => 'nullable|integer|min:1',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip|max:10240',
        ]);

        $originalTitle = $assignment->title;

        $assignment->title = $request->title;
        $assignment->description = $request->description;
        $assignment->due_date = $request->dueDate;
        if ($request->has('totalMarks') && $request->totalMarks) {
            $assignment->total_marks = $request->totalMarks;
        }

        // Handle new attachments
        if ($request->hasFile('attachments')) {
            // Delete old attachments
            if ($assignment->attachments) {
                foreach ($assignment->attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        Storage::disk('public')->delete($attachment['path']);
                    }
                }
            }

            // Upload new attachments
            $attachmentPaths = [];
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('buddy/assignments/' . $matchId, 'public');
                $attachmentPaths[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                ];
            }
            $assignment->attachments = $attachmentPaths;
        }

        $assignment->save();

        // Re-evaluate submission statuses based on new due date
        $newDueDate = $assignment->due_date;
        $submissions = BuddyAssignmentSubmission::where('assignment_id', $assignment->id)->get();
        foreach ($submissions as $submission) {
            $newStatus = $submission->submitted_at->gt($newDueDate) ? 'late' : 'on-time';
            if ($submission->status !== $newStatus) {
                $submission->status = $newStatus;
                $submission->save();
            }
        }

        // Also update sibling assignments and their submissions
        $siblingMatchIds = array_diff($this->getMentorMatchIds($matchId), [$matchId]);
        if (!empty($siblingMatchIds)) {
            $siblingAssignments = BuddyAssignment::whereIn('match_id', $siblingMatchIds)
                ->where('title', $originalTitle)
                ->get();
            foreach ($siblingAssignments as $siblingAssignment) {
                $siblingAssignment->title = $assignment->title;
                $siblingAssignment->description = $assignment->description;
                $siblingAssignment->due_date = $assignment->due_date;
                if ($request->has('totalMarks') && $request->totalMarks) {
                    $siblingAssignment->total_marks = $assignment->total_marks;
                }
                $siblingAssignment->save();

                // Re-evaluate sibling submissions too
                $siblingSubmissions = BuddyAssignmentSubmission::where('assignment_id', $siblingAssignment->id)->get();
                foreach ($siblingSubmissions as $sub) {
                    $newStatus = $sub->submitted_at->gt($siblingAssignment->due_date) ? 'late' : 'on-time';
                    if ($sub->status !== $newStatus) {
                        $sub->status = $newStatus;
                        $sub->save();
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Assignment updated successfully',
            'assignment' => [
                'id' => (string) $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'dueDate' => $assignment->due_date->format('Y-m-d'),
                'totalMarks' => $assignment->total_marks,
                'attachments' => collect($assignment->attachments ?? [])->pluck('name')->toArray(),
                'createdDate' => $assignment->created_at->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Download assignment attachment
     */
    public function downloadAssignmentAttachment(Request $request, int $matchId, int $assignmentId, string $filename): mixed
    {
        // Match access already validated by middleware

        $assignment = BuddyAssignment::where('match_id', $matchId)
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }

        // Find the attachment by filename
        $attachment = collect($assignment->attachments ?? [])->first(function ($att) use ($filename) {
            $attachmentName = is_array($att) ? ($att['name'] ?? '') : $att;
            return $attachmentName === $filename;
        });

        if (!$attachment) {
            return response()->json(['error' => 'Attachment not found'], 404);
        }

        // Get the file path
        $filePath = is_array($attachment) ? ($attachment['path'] ?? '') : '';
        
        if (empty($filePath) || !Storage::disk('public')->exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // Return file stream for preview (not forced download)
        $mime = Storage::disk('public')->mimeType($filePath);
        $file = Storage::disk('public')->get($filePath);
        return response($file, 200)
            ->header('Content-Type', $mime)
            ->header('Content-Length', strlen($file));
    }

    /**
     * Submit assignment
     */
    public function submitAssignment(Request $request, int $matchId, int $assignmentId): JsonResponse
    {
        // Get data from middleware
        $participant = $this->getParticipant($request);
        $isMentee = $request->attributes->get('isMentee');
        
        // Only mentee can submit assignments
        if (!$isMentee) {
            return response()->json(['error' => 'Only mentees can submit assignments'], 403);
        }

        $assignment = BuddyAssignment::where('match_id', $matchId)
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip|max:10240',
        ]);

        // Check if already submitted
        $existingSubmission = BuddyAssignmentSubmission::where('assignment_id', $assignmentId)
            ->where('participant_id', $participant->id)
            ->first();

        if ($existingSubmission) {
            // Delete old submission file and update
            Storage::disk('public')->delete($existingSubmission->file_path);
        }

        $file = $request->file('file');
        $path = $file->store('buddy/submissions/' . $matchId . '/' . $assignmentId, 'public');

        $status = now()->gt($assignment->due_date) ? 'late' : 'on-time';

        if ($existingSubmission) {
            $existingSubmission->update([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'status' => $status,
                'submitted_at' => now(),
            ]);
            $submission = $existingSubmission;
        } else {
            $submission = BuddyAssignmentSubmission::create([
                'assignment_id' => $assignmentId,
                'participant_id' => $participant->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'status' => $status,
                'submitted_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Assignment submitted successfully',
            'submission' => [
                'id' => (string) $submission->id,
                'fileName' => $submission->file_name,
                'submittedDate' => $submission->submitted_at->format('Y-m-d'),
                'status' => $submission->status,
                'marks' => $submission->marks,
                'feedback' => $submission->feedback,
            ],
        ]);
    }

    /**
     * Get assignment submissions (for mentors)
     */
    public function getAssignmentSubmissions(Request $request, int $matchId, int $assignmentId): JsonResponse
    {
        // Get data from middleware
        $isMentor = $request->attributes->get('isMentor');
        
        // Only mentor can view all submissions
        if (!$isMentor) {
            return response()->json(['error' => 'Only mentors can view all submissions'], 403);
        }

        $assignment = BuddyAssignment::where('match_id', $matchId)
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }

        // Aggregate submissions across all mentor's matches (sibling assignments with same title)
        $allMatchIds = $this->getMentorMatchIds($matchId);
        $siblingAssignmentIds = BuddyAssignment::whereIn('match_id', $allMatchIds)
            ->where('title', $assignment->title)
            ->pluck('id');

        $submissions = BuddyAssignmentSubmission::whereIn('assignment_id', $siblingAssignmentIds)
            ->with('participant')
            ->get()
            ->map(function ($submission) {
                return [
                    'id' => (string) $submission->id,
                    'assignmentId' => (string) $submission->assignment_id,
                    'studentName' => $submission->participant->full_name,
                    'studentId' => $submission->participant->student_id,
                    'fileName' => $submission->file_name,
                    'submittedDate' => $submission->submitted_at->format('Y-m-d'),
                    'status' => $submission->status,
                    'marks' => $submission->marks,
                    'feedback' => $submission->feedback,
                ];
            });

        return response()->json([
            'assignment' => [
                'id' => (string) $assignment->id,
                'title' => $assignment->title,
                'totalMarks' => $assignment->total_marks,
            ],
            'submissions' => $submissions,
        ]);
    }

    /**
     * Grade assignment submission (for mentors)
     */
    public function gradeSubmission(Request $request, int $matchId, int $assignmentId, int $submissionId): JsonResponse
    {
        // Get data from middleware
        $isMentor = $request->attributes->get('isMentor');
        
        // Only mentor can grade
        if (!$isMentor) {
            return response()->json(['error' => 'Only mentors can grade submissions'], 403);
        }

        $assignment = BuddyAssignment::where('match_id', $matchId)
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }

        // Look across all sibling assignments (same title, same mentor) for this submission
        $allMatchIds = $this->getMentorMatchIds($matchId);
        $siblingAssignmentIds = BuddyAssignment::whereIn('match_id', $allMatchIds)
            ->where('title', $assignment->title)
            ->pluck('id');

        $submission = BuddyAssignmentSubmission::whereIn('assignment_id', $siblingAssignmentIds)
            ->where('id', $submissionId)
            ->first();

        if (!$submission) {
            return response()->json(['error' => 'Submission not found'], 404);
        }

        $request->validate([
            'marks' => 'required|integer|min:0|max:' . $assignment->total_marks,
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'marks' => $request->marks,
            'feedback' => $request->feedback,
        ]);

        return response()->json([
            'message' => 'Submission graded successfully',
            'submission' => [
                'id' => (string) $submission->id,
                'marks' => $submission->marks,
                'feedback' => $submission->feedback,
            ],
        ]);
    }

    /**
     * Download assignment submission
     */
    public function downloadSubmission(Request $request, int $matchId, int $assignmentId, int $submissionId): mixed
    {
        // Match access already validated by middleware

        $assignment = BuddyAssignment::where('match_id', $matchId)
            ->where('id', $assignmentId)
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }

        // Look across all sibling assignments (same title, same mentor) for this submission
        $allMatchIds = $this->getMentorMatchIds($matchId);
        $siblingAssignmentIds = BuddyAssignment::whereIn('match_id', $allMatchIds)
            ->where('title', $assignment->title)
            ->pluck('id');

        $submission = BuddyAssignmentSubmission::whereIn('assignment_id', $siblingAssignmentIds)
            ->where('id', $submissionId)
            ->first();

        if (!$submission) {
            return response()->json(['error' => 'Submission not found'], 404);
        }

        return Storage::disk('public')->download($submission->file_path, $submission->file_name);
    }

    /**
     * Delete assignment submission (for mentees to remove their own submission)
     */
    public function deleteSubmission(Request $request, int $matchId, int $assignmentId): JsonResponse
    {
        // Get data from middleware
        $participant = $this->getParticipant($request);
        $isMentee = $request->attributes->get('isMentee');
        
        // Only mentee can delete their own submission
        if (!$isMentee) {
            return response()->json(['error' => 'Only mentees can delete submissions'], 403);
        }

        $submission = BuddyAssignmentSubmission::where('assignment_id', $assignmentId)
            ->where('participant_id', $participant->id)
            ->first();

        if (!$submission) {
            return response()->json(['error' => 'Submission not found'], 404);
        }

        // Delete file from storage
        Storage::disk('public')->delete($submission->file_path);
        
        // Delete submission record
        $submission->delete();

        return response()->json([
            'message' => 'Submission deleted successfully',
        ]);
    }

    /**
     * Download own assignment submission (for mentees)
     */
    public function downloadOwnSubmission(Request $request, int $matchId, int $assignmentId): mixed
    {
        // Get data from middleware
        $participant = $this->getParticipant($request);

        $submission = BuddyAssignmentSubmission::where('assignment_id', $assignmentId)
            ->where('participant_id', $participant->id)
            ->first();

        if (!$submission) {
            return response()->json(['error' => 'Submission not found'], 404);
        }

        return Storage::disk('public')->download($submission->file_path, $submission->file_name);
    }

    /**
     * Format file size to human readable format
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 1) . ' ' . $units[$pow];
    }
}
