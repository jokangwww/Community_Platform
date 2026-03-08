<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileChangeLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserProfileCorrectionController extends Controller
{
    // Load the main page listing and apply request filters if provided.
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', '');

        $query = User::query();

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('student_id', 'like', '%' . $search . '%')
                    ->orWhere('ic_number', 'like', '%' . $search . '%');
            });
        }

        if (in_array($role, ['student', 'club', 'staff', 'admin'], true)) {
            $query->where('role', $role);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.user-profile-corrections.index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
        ]);
    }

    // Load the edit form for an existing record after ownership/access checks.
    public function edit(User $user): View
    {
        $adminMeta = $this->adminMeta($user);

        $logs = ProfileChangeLog::with('admin')
            ->where('target_user_id', $user->id)
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.user-profile-corrections.edit', [
            'targetUser' => $user,
            'adminMeta' => $adminMeta,
            'logs' => $logs,
        ]);
    }

    // Validate the request and update the existing record.
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:student,staff,club,admin'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'student_id' => ['nullable', 'string', 'max:255', Rule::unique('users', 'student_id')->ignore($user->id)],
            'ic_number' => [
                Rule::requiredIf((string) $request->input('role') === 'student'),
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'ic_number')->ignore($user->id),
            ],
            'programme' => [
                Rule::requiredIf((string) $request->input('role') === 'student'),
                'nullable',
                'string',
                'max:255',
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'position' => ['nullable', 'string', 'max:255'],
            'contact_information' => ['nullable', 'string', 'max:255'],
            'responsibilities' => ['nullable', 'string', 'max:2000'],
            'change_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $before = [
            'role' => $user->role,
            'name' => $user->name,
            'email' => $user->email,
            'student_id' => $user->student_id,
            'ic_number' => $user->ic_number,
            'programme' => $user->programme,
            'display_name' => $user->display_name,
            'bio' => $user->bio,
        ];

        $normalizedStudentId = ($validated['role'] ?? '') === 'club'
            ? null
            : ($validated['student_id'] ?? null);

        $user->fill([
            'role' => $validated['role'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'student_id' => $normalizedStudentId,
            'ic_number' => ($validated['role'] ?? '') === 'student' ? ($validated['ic_number'] ?? null) : null,
            'programme' => ($validated['role'] ?? '') === 'student' ? ($validated['programme'] ?? null) : null,
            'display_name' => $validated['display_name'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ]);

        $changes = [];
        foreach ($before as $field => $oldValue) {
            $newValue = $user->{$field};
            if ((string) ($oldValue ?? '') !== (string) ($newValue ?? '')) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        $metaFields = [];
        if (($validated['role'] ?? $user->role) === 'admin') {
            $adminMeta = $this->adminMeta($user);
            $beforeMeta = [
                'position' => $adminMeta->position ?? null,
                'contact_information' => $adminMeta->contact_information ?? null,
                'responsibilities' => $adminMeta->responsibilities ?? null,
            ];

            $metaFields = [
                'position' => $validated['position'] ?? null,
                'contact_information' => $validated['contact_information'] ?? null,
                'responsibilities' => $validated['responsibilities'] ?? null,
            ];

            foreach ($beforeMeta as $field => $oldValue) {
                $newValue = $metaFields[$field] ?? null;
                if ((string) ($oldValue ?? '') !== (string) ($newValue ?? '')) {
                    $changes[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        if ($changes === []) {
            return back()->with('status', 'No changes detected.');
        }

        DB::transaction(function () use ($request, $user, $metaFields, $changes, $validated): void {
            $nextRole = $validated['role'] ?? $user->role;
            $user->staff_id = $nextRole === 'admin'
                ? ($user->staff_id ?: ($user->student_id ?: ('ADMIN-' . $user->id)))
                : null;
            $user->position = $nextRole === 'admin' ? ($metaFields['position'] ?? null) : null;
            $user->contact_information = $nextRole === 'admin' ? ($metaFields['contact_information'] ?? null) : null;
            $user->responsibilities = $nextRole === 'admin' ? ($metaFields['responsibilities'] ?? null) : null;
            $user->save();

            ProfileChangeLog::create([
                'admin_id' => $request->user()->id,
                'target_user_id' => $user->id,
                'changed_fields' => $changes,
                'note' => $validated['change_note'] ?? null,
            ]);
        });

        return back()->with('status', 'Profile corrected and logged successfully.');
    }

    // Helper method: admin meta.
    private function adminMeta(User $user): object
    {
        if ($user->role !== 'admin') {
            return (object) [
                'staff_id' => null,
                'position' => null,
                'contact_information' => null,
                'responsibilities' => null,
            ];
        }

        return (object) [
            'staff_id' => $user->staff_id ?: ($user->student_id ?: 'ADMIN-' . $user->id),
            'position' => $user->position,
            'contact_information' => $user->contact_information,
            'responsibilities' => $user->responsibilities,
        ];
    }
}
