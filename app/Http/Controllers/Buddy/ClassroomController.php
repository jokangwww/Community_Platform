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

class ClassroomController extends Controller
{
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
     * Get classroom data for a match (materials, quizzes, assignments)
     */
    public function getClassroomData(Request $request, int $matchId): JsonResponse
    {
        // Get data from middleware
        $participant = $this->getParticipant($request);
        $match = $this->getMatch($request);
        $isMentor = $request->attributes->get('isMentor');

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

        // Get mentees for this match (for mentor view)
        $mentees = [];
        if ($isMentor) {
            // Get all mentees in this specific match via pivot table
            $menteeParticipants = $match->mentees()->get();
            
            foreach ($menteeParticipants as $menteeParticipant) {
                $mentees[] = [
                    'id' => (string) $menteeParticipant->id,
                    'name' => $menteeParticipant->full_name,
                    'studentId' => $menteeParticipant->student_id,
                ];
            }
        }

        return response()->json([
            'materials' => $materials,
            'quizzes' => $quizzes,
            'assignments' => $assignments,
            'mentees' => $mentees,
            'userRole' => $isMentor ? 'mentor' : 'mentee',
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
            ->with(['attempts.participant'])
            ->first();

        if (!$quiz) {
            return response()->json(['error' => 'Quiz not found'], 404);
        }

        $attempts = $quiz->attempts->map(function ($attempt) {
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
            'totalMarks' => 'required|integer|min:1',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip|max:10240',
        ]);

        $assignment->title = $request->title;
        $assignment->description = $request->description;
        $assignment->due_date = $request->dueDate;
        $assignment->total_marks = $request->totalMarks;

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
            ->with(['submissions.participant'])
            ->first();

        if (!$assignment) {
            return response()->json(['error' => 'Assignment not found'], 404);
        }

        $submissions = $assignment->submissions->map(function ($submission) {
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

        $submission = BuddyAssignmentSubmission::where('assignment_id', $assignmentId)
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

        $submission = BuddyAssignmentSubmission::where('assignment_id', $assignmentId)
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
