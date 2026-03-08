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
    // Admin location management screen: list uploaded campus maps and their clickable points.
    public function index(): View
    {
        // Eager-load points so each map can render its markers without N+1 queries.
        $maps = LocationMap::with('points')->latest()->get();

        return view('admin.locations.index', [
            'maps' => $maps,
        ]);
    }

    // Upload a new campus map image that will later contain event location markers.
    public function storeMap(Request $request): RedirectResponse
    {
        // Validate map metadata and ensure the uploaded file is an image within size limit.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'map_image' => ['required', 'image', 'max:5120'],
        ]);

        // Store the map image on the public disk
        $imagePath = $request->file('map_image')->store('maps', 'public');

        // Create the map record
        LocationMap::create([
            'name' => $validated['name'],
            'image_path' => $imagePath,
        ]);

        return back()->with('status', 'Map uploaded successfully.');
    }

    // Delete a map and its stored image file when the map is no longer needed.
    public function destroyMap(LocationMap $locationMap): RedirectResponse
    {
        // Remove the physical image first, then delete the database record.
        Storage::disk('public')->delete($locationMap->image_path);
        $locationMap->delete();

        return back()->with('status', 'Map removed successfully.');
    }

    // Add a marker/point to a specific map using percentage-based coordinates.
    public function storePoint(Request $request, LocationMap $locationMap): RedirectResponse
    {
        // Coordinates are stored as percentages so markers stay aligned on responsive image scaling.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'x_percent' => ['required', 'numeric', 'between:0,100'],
            'y_percent' => ['required', 'numeric', 'between:0,100'],
        ]);

        // Save the point under the selected map via the relationship.
        $locationMap->points()->create($validated);

        return back()->with('status', 'Location point added successfully.');
    }

    // Remove a marker from a map after confirming it belongs to the requested parent map.
    public function destroyPoint(LocationMap $locationMap, LocationPoint $point): RedirectResponse
    {
        // Guard against deleting a point through the wrong map URL.
        if ($point->location_map_id !== $locationMap->id) {
            abort(404);
        }

        $point->delete();

        return back()->with('status', 'Location point removed successfully.');
    }
}
