<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    // Load the main page listing and apply request filters if provided.
    public function index(): View
    {
        return view('admin.departments.index', [
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    // Validate the request and create a new record from submitted form data.
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')],
        ]);

        Department::create([
            'name' => trim($validated['name']),
        ]);

        return back()->with('status', 'Department added successfully.');
    }

    // Delete or close the selected record after access checks.
    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return back()->with('status', 'Department removed successfully.');
    }
}
