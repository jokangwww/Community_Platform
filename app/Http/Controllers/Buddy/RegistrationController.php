<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buddy\RegistrationRequest;
use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use App\Models\BuddyMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    /**
     * Sanitize filename to prevent path traversal attacks
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove any path components
        $filename = basename($filename);
        
        // Remove any characters that could be used for path traversal
        $filename = preg_replace('/[\\.]{2,}/', '', $filename); // Remove ..
        $filename = preg_replace('/[\\/\\\\]/', '', $filename); // Remove / and \
        $filename = preg_replace('/[\\x00-\\x1f]/', '', $filename); // Remove null bytes and control chars
        
        // Only allow alphanumeric, dots, dashes, and underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        return $filename;
    }

    /**
     * Generate a secure random filename while preserving extension
     */
    private function generateSecureFilename(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Whitelist allowed extensions
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($extension, $allowedExtensions)) {
            $extension = 'pdf'; // Default to pdf if extension is suspicious
        }
        
        // Generate random filename with timestamp
        return Str::random(32) . '_' . time() . '.' . $extension;
    }

    /**
     * Register a new participant (mentor or mentee)
     */
    public function register(RegistrationRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Handle document upload
            $documentPath = null;
            $documentName = null;

            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $originalName = $file->getClientOriginalName();
                
                // Sanitize the original filename for display
                $documentName = $this->sanitizeFilename($originalName);
                
                // Generate a secure random filename for storage
                $secureFilename = $this->generateSecureFilename($originalName);
                
                // Store with secure filename in a dedicated folder
                $documentPath = $file->storeAs('buddy-documents', $secureFilename, 'public');
            }

            // Determine initial status based on role
            $status = $request->role === 'mentor' ? 'pending' : 'active';

            // Determine priority tier (for mentees only)
            $priorityTier = null;
            if ($request->role === 'mentee') {
                if ($request->is_repeater) {
                    $priorityTier = 'high';
                } else {
                    $priorityTier = 'normal';
                }
            }

            // Handle subject/skill selection or creation
            $subjectId = $this->resolveSubjectId($request);

            // Create participant
            $participant = BuddyParticipant::create([
                'user_id' => Auth::check() ? Auth::id() : null,
                'full_name' => $request->full_name,
                'student_id' => $request->student_id,
                'course' => $request->course,
                'faculty' => $request->faculty,
                'year_of_study' => $request->year_of_study,
                'cgpa' => $request->cgpa,
                'role' => $request->role,
                'is_repeater' => $request->is_repeater ?? false,
                'subject_id' => $subjectId,
                'document_path' => $documentPath,
                'document_name' => $documentName,
                'status' => $status,
                'priority_tier' => $priorityTier,
                'rating' => 3.0, // Default rating
            ]);

            // Legacy: Also attach to pivot table for backwards compatibility
            if ($subjectId) {
                $participant->subjects()->attach($subjectId);
            }

            // If mentee and active, calculate waitlist position
            if ($request->role === 'mentee') {
                $this->updateWaitlistPositions();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->role === 'mentor'
                    ? 'Registration submitted for verification'
                    : 'Registration successful',
                'data' => [
                    'id' => $participant->id,
                    'status' => $participant->status,
                    'priority_tier' => $participant->priority_tier,
                    'is_repeater' => $participant->is_repeater,
                    'subject' => $participant->subject ? [
                        'id' => $participant->subject->id,
                        'name' => $participant->subject->name,
                        'type' => $participant->subject->type,
                    ] : null,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded file if exists
            if (isset($documentPath) && $documentPath) {
                Storage::disk('public')->delete($documentPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get registration status for current user
     */
    public function getStatus(Request $request): JsonResponse
    {
        $studentId = $request->query('student_id');

        if (!$studentId && Auth::check()) {
            $studentId = Auth::user()->student_id;
        }

        // If still no student_id, return not-registered (user hasn't set up a student ID)
        if (!$studentId) {
            return response()->json([
                'success' => true,
                'data' => ['registered' => false]
            ]);
        }

        $participant = BuddyParticipant::with('subject')
            ->where('student_id', $studentId)
            ->first();

        if (!$participant) {
            return response()->json([
                'success' => true,
                'data' => [
                    'registered' => false
                ]
            ]);
        }

        // Check if participant has an active match via pivot table
        $match = BuddyMatch::whereHas('participants', function ($query) use ($participant) {
                $query->where('buddy_match_participants.participant_id', $participant->id);
            })
            ->where('status', 'active')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'registered' => true,
                'id' => $participant->id,
                'full_name' => $participant->full_name,
                'student_id' => $participant->student_id,
                'role' => $participant->role,
                'status' => $participant->status,
                'priority_tier' => $participant->priority_tier,
                'is_repeater' => $participant->is_repeater,
                'waitlist_position' => $participant->waitlist_position,
                'has_active_match' => $match !== null,
                'match_id' => $match?->id,
                'subject' => $participant->subject ? [
                    'id' => $participant->subject->id,
                    'name' => $participant->subject->name,
                    'type' => $participant->subject->type,
                ] : null,
                'rating' => $participant->rating,
                'created_at' => $participant->created_at,
            ]
        ]);
    }

    /**
     * Resolve subject ID from request (existing ID or create new)
     */
    protected function resolveSubjectId($request): ?int
    {
        // If subject_id is provided, use it directly
        if ($request->subject_id) {
            $subject = BuddySubject::find($request->subject_id);
            if ($subject) {
                return $subject->id;
            }
        }

        // If new subject/skill data is provided, create or find existing
        if ($request->new_subject_name) {
            $name = trim($request->new_subject_name);
            $code = $request->new_subject_code ? trim($request->new_subject_code) : null;
            $type = $request->subject_type ?? 'skill';

            // Check for existing entry with same name (case-insensitive)
            $existing = BuddySubject::where('type', $type)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->first();

            if ($existing) {
                return $existing->id;
            }

            // Create new subject/skill
            $subject = BuddySubject::create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'is_active' => true,
            ]);

            return $subject->id;
        }

        return null;
    }

    /**
     * Update waitlist positions for all mentees
     */
    protected function updateWaitlistPositions(): void
    {
        // Get all active mentees ordered by priority
        $mentees = BuddyParticipant::mentees()
            ->whereIn('status', ['active', 'pending'])
            ->whereDoesntHave('menteeMatches', function ($query) {
                $query->where('status', 'active');
            })
            ->orderByRaw("CASE priority_tier 
                WHEN 'high' THEN 1 
                WHEN 'normal' THEN 2 
                WHEN 'low' THEN 3 
                END")
            ->orderBy('created_at', 'asc')
            ->get();

        $position = 1;
        foreach ($mentees as $mentee) {
            $mentee->waitlist_position = $position++;
            $mentee->save();
        }
    }
}
