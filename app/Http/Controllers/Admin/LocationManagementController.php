<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LocationMap;
use App\Models\LocationPoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LocationManagementController extends Controller
{
    public function index(): View
    {
        $maps = LocationMap::with('points')->latest()->get();

        return view('admin.locations.index', [
            'maps' => $maps,
        ]);
    }

    public function storeMap(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'map_image' => ['required', 'image', 'max:5120'],
        ]);

        $imagePath = $request->file('map_image')->store('maps', 'public');

        LocationMap::create([
            'name' => $validated['name'],
            'image_path' => $imagePath,
        ]);

        return back()->with('status', 'Map uploaded successfully.');
    }

    public function destroyMap(LocationMap $locationMap): RedirectResponse
    {
        Storage::disk('public')->delete($locationMap->image_path);
        $locationMap->delete();

        return back()->with('status', 'Map removed successfully.');
    }

    public function storePoint(Request $request, LocationMap $locationMap): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'x_percent' => ['required', 'numeric', 'between:0,100'],
            'y_percent' => ['required', 'numeric', 'between:0,100'],
        ]);

        $locationMap->points()->create($validated);

        return back()->with('status', 'Location point added successfully.');
    }

    public function destroyPoint(LocationMap $locationMap, LocationPoint $point): RedirectResponse
    {
        if ($point->location_map_id !== $locationMap->id) {
            abort(404);
        }

        $point->delete();

        return back()->with('status', 'Location point removed successfully.');
    }
}
