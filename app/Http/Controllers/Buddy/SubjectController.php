<?php

namespace App\Http\Controllers\Buddy;

use App\Http\Controllers\Controller;
use App\Models\BuddySubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Search subjects/skills by name or code
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->query('q', '');
        $type = $request->query('type'); // 'subject', 'skill', or null for all

        $query = BuddySubject::active();

        if ($type) {
            $query->where('type', $type);
        }

        if ($term) {
            $query->search($term);
        }

        $results = $query->orderBy('type')
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'type' => $item->type,
                    'display_name' => $item->display_name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get all subjects (type = subject)
     */
    public function getSubjects(): JsonResponse
    {
        $subjects = BuddySubject::active()
            ->subjects()
            ->orderBy('code')
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'display_name' => $item->display_name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $subjects,
        ]);
    }

    /**
     * Get all skills (type = skill)
     */
    public function getSkills(): JsonResponse
    {
        $skills = BuddySubject::active()
            ->skills()
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $skills,
        ]);
    }

    /**
     * Create a new subject or skill
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'required|in:subject,skill',
        ]);

        $name = trim($request->name);
        $code = $request->code ? trim($request->code) : null;
        $type = $request->type;

        // Check for existing similar entry
        $existing = BuddySubject::where('type', $type)
            ->where(function ($query) use ($name, $code) {
                $query->whereRaw('LOWER(name) = ?', [strtolower($name)]);
                if ($code) {
                    $query->orWhereRaw('LOWER(code) = ?', [strtolower($code)]);
                }
            })
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $existing->id,
                    'code' => $existing->code,
                    'name' => $existing->name,
                    'type' => $existing->type,
                    'display_name' => $existing->display_name,
                    'is_existing' => true,
                ],
                'message' => 'Similar entry already exists',
            ]);
        }

        // Create new entry
        $subject = BuddySubject::create([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $subject->id,
                'code' => $subject->code,
                'name' => $subject->name,
                'type' => $subject->type,
                'display_name' => $subject->display_name,
                'is_existing' => false,
            ],
            'message' => 'Created successfully',
        ], 201);
    }

    /**
     * Get a specific subject/skill by ID
     */
    public function show(int $id): JsonResponse
    {
        $subject = BuddySubject::find($id);

        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $subject->id,
                'code' => $subject->code,
                'name' => $subject->name,
                'type' => $subject->type,
                'display_name' => $subject->display_name,
            ],
        ]);
    }
}
