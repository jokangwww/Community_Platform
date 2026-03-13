<?php

namespace App\Http\Controllers\PollPetition;

use App\Http\Controllers\Controller;
use App\Models\Petition;
use App\Models\PetitionAttachment;
use App\Models\PetitionSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PetitionController extends Controller
{
    /**
     * List petitions with optional search.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $query = Petition::with(['user', 'attachments', 'supports.user'])
            ->where('status', '!=', 'disabled');

        // Search
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'latest');
        if ($sortBy === 'supporters') {
            $query->withCount('supports')->orderByDesc('supports_count');
        } else {
            $query->orderByDesc('created_at');
        }

        $petitions = $query->get();

        $result = $petitions->map(function ($petition) use ($userId) {
            return $this->formatPetitionCard($petition, $userId);
        });

        return response()->json($result);
    }

    /**
     * Show single petition with full details (attachments + supporters).
     */
    public function show(int $id): JsonResponse
    {
        $userId = Auth::id();

        $petition = Petition::with(['user', 'attachments', 'supports.user'])
            ->findOrFail($id);

        return response()->json($this->formatPetitionFull($petition, $userId));
    }

    /**
     * Create a new petition (1 petition per 30 days limit).
     */
    public function store(Request $request): JsonResponse
    {
        $userId = Auth::id();

        // Check 30-day cooldown
        $lastPetition = Petition::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        if ($lastPetition && $lastPetition->created_at->diffInDays(now()) < 30) {
            $nextDate = $lastPetition->created_at->addDays(30)->toDateString();
            return response()->json([
                'message' => 'You can only create one petition every 30 days.',
                'next_available_date' => $nextDate,
            ], 422);
        }

        $validated = $request->validate([
            'title'             => 'required|string|max:150',
            'description'       => 'required|string|max:2000',
            'proposed_solution' => 'required|string|max:2000',
            'supporter_goal'    => 'nullable|integer|min:100|max:10000',
            'attachments'       => 'nullable|array|max:5',
            'attachments.*'     => 'file|max:10240', // 10 MB each
        ]);

        $petition = DB::transaction(function () use ($validated, $request, $userId) {
            $petition = Petition::create([
                'user_id'           => $userId,
                'title'             => $validated['title'],
                'description'       => $validated['description'],
                'proposed_solution' => $validated['proposed_solution'],
                'supporter_goal'    => $validated['supporter_goal'] ?? 500,
            ]);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store("petitions/{$petition->id}", 'public');
                    PetitionAttachment::create([
                        'petition_id' => $petition->id,
                        'file_name'   => $file->getClientOriginalName(),
                        'file_path'   => $path,
                        'file_type'   => $file->getClientMimeType(),
                        'file_size'   => $file->getSize(),
                    ]);
                }
            }

            return $petition;
        });

        $petition->load(['user', 'attachments', 'supports.user']);

        return response()->json($this->formatPetitionFull($petition, $userId), 201);
    }

    /**
     * Support a petition (optionally with a comment).
     */
    public function support(Request $request, int $id): JsonResponse
    {
        $userId = Auth::id();
        $petition = Petition::findOrFail($id);

        if ($petition->status !== 'active') {
            return response()->json(['message' => 'This petition is no longer active.'], 422);
        }

        if ($petition->hasUserSupported($userId)) {
            return response()->json(['message' => 'You have already supported this petition.'], 422);
        }

        $validated = $request->validate([
            'comment' => 'nullable|string|max:500',
        ]);

        PetitionSupport::create([
            'petition_id' => $petition->id,
            'user_id'     => $userId,
            'comment'     => $validated['comment'] ?? null,
        ]);

        $petition->load(['user', 'attachments', 'supports.user']);

        return response()->json($this->formatPetitionFull($petition, $userId));
    }

    /**
     * Check whether the current user can create a petition.
     */
    public function canCreate(): JsonResponse
    {
        $userId = Auth::id();

        $lastPetition = Petition::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        $canCreate = true;
        $nextDate = null;

        if ($lastPetition && $lastPetition->created_at->diffInDays(now()) < 30) {
            $canCreate = false;
            $nextDate = $lastPetition->created_at->addDays(30)->toDateString();
        }

        return response()->json([
            'can_create'          => $canCreate,
            'next_available_date' => $nextDate,
        ]);
    }

    /**
     * Download a petition attachment.
     */
    public function downloadAttachment(int $petitionId, int $attachmentId)
    {
        $attachment = PetitionAttachment::where('petition_id', $petitionId)
            ->findOrFail($attachmentId);

        $fullPath = Storage::disk('public')->path($attachment->file_path);
        return response()->download($fullPath, $attachment->file_name);
    }

    /**
     * Get archived (closed / disabled) petitions.
     */
    public function archived(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $query = Petition::with(['user', 'attachments', 'supports.user'])
            ->where(function ($q) {
                $q->where('status', 'closed')
                  ->orWhere('status', 'disabled');
            });

        // Search
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'date');
        if ($sortBy === 'popularity') {
            $query->withCount('supports')->orderByDesc('supports_count');
        } else {
            $query->orderByDesc('created_at');
        }

        $petitions = $query->get();

        $result = $petitions->map(function ($petition) use ($userId) {
            return $this->formatPetitionCard($petition, $userId);
        });

        return response()->json($result);
    }

    /* ── Formatting helpers ───────────────────────── */

    private function formatPetitionCard(Petition $petition, int $userId): array
    {
        return [
            'id'              => (string) $petition->id,
            'title'           => $petition->title,
            'description'     => $petition->description,
            'proposedSolution'=> $petition->proposed_solution,
            'author'          => $petition->user->nickname ?? $petition->user->name,
            'createdAt'       => $petition->created_at->format('M j, Y'),
            'supportCount'    => $petition->supports->count(),
            'goal'            => $petition->supporter_goal ?? 500,
            'status'          => $petition->status,
            'hasSupported'    => $petition->hasUserSupported($userId),
            'attachmentCount' => $petition->attachments->count(),
            'commentCount'    => $petition->supports->filter(fn($s) => $s->comment !== null)->count(),
        ];
    }

    private function formatPetitionFull(Petition $petition, int $userId): array
    {
        return [
            'id'              => (string) $petition->id,
            'title'           => $petition->title,
            'description'     => $petition->description,
            'proposedSolution'=> $petition->proposed_solution,
            'author'          => $petition->user->nickname ?? $petition->user->name,
            'createdAt'       => $petition->created_at->format('M j, Y'),
            'supportCount'    => $petition->supports->count(),
            'goal'            => $petition->supporter_goal ?? 500,
            'status'          => $petition->status,
            'hasSupported'    => $petition->hasUserSupported($userId),
            'attachmentCount' => $petition->attachments->count(),
            'commentCount'    => $petition->supports->filter(fn($s) => $s->comment !== null)->count(),
            'attachments'     => $petition->attachments->map(fn($a) => [
                'id'   => (string) $a->id,
                'name' => $a->file_name,
                'type' => $a->file_type,
                'size' => $a->formatted_size,
                'url'  => route('poll-petition.petitions.attachment', [$petition->id, $a->id]),
            ]),
            'supporters'      => $petition->supports
                ->sortByDesc('created_at')
                ->values()
                ->map(fn($s) => [
                    'id'          => (string) $s->id,
                    'nickname'    => $s->user->nickname ?? $s->user->name,
                    'comment'     => $s->comment,
                    'supportedAt' => $s->created_at->diffForHumans(),
                ]),
        ];
    }
}
