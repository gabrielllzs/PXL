<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SavedLocation;
use Illuminate\Support\Facades\Auth;

class SavedLocationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $locations = $user->savedLocations()->orderBy('created_at', 'desc')->get();

        return response()->json($locations);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'zoom' => ['nullable', 'numeric', 'min:0', 'max:22'],
        ]);

        // Check if user already has 3 locations
        $locationCount = $user->savedLocations()->count();
        if ($locationCount >= 3) {
            return response()->json([
                'error' => 'You can only save up to 3 locations'
            ], 400);
        }

        // Generate unique share key
        do {
            $shareKey = bin2hex(random_bytes(8));
        } while (SavedLocation::where('share_key', $shareKey)->exists());

        $location = SavedLocation::create([
            'user_id' => $user->id,
            'name' => $validated['name'] ?? null,
            'share_key' => $shareKey,
            'longitude' => $validated['longitude'],
            'latitude' => $validated['latitude'],
            'zoom' => $validated['zoom'] ?? null,
        ]);

        return response()->json($location, 201);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $location = SavedLocation::where('user_id', $user->id)->findOrFail($id);
        $location->delete();

        return response()->json(['message' => 'Location deleted successfully']);
    }

    public function showByKey($key)
    {
        $location = SavedLocation::where('share_key', $key)->firstOrFail();

        return response()->json([
            'id' => $location->id,
            'name' => $location->name,
            'longitude' => $location->longitude,
            'latitude' => $location->latitude,
            'zoom' => $location->zoom,
        ]);
    }
}
