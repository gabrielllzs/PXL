<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Groups;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function index()
    {
        // for the leaderboard
        $groups = Groups::with('members')->get();
        return response()->json(
            $groups->map(function ($group) {
                return [
                    'name' => $group->name,
                    'total_group_pixels' => $group->members()->sum('pixels_placed'),
                ];
            })
        );
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:16',
        ]);

        $user = Auth::user();

        $invite_code = bin2hex(random_bytes(6));

        $group = Groups::create([
            'name' => $validated['name'],
            'owner_id' => $user->id,
            'invite_code' => $invite_code,
        ]);

        // Add the owner as a member with 'owner' role
        $user->groups()->attach($group->id, ['role' => 'owner']);

        return response()->json($group, 201);
    }

    public function showMyGroup()
    {
        $user = Auth::user();
        
        // Get the user's first group (current/primary group)
        $group = $user->groups()->with('members')->first();

        if (!$group) {
            return response()->json(null, 404);
        }

        $members = $group->members()->get()->map(function ($member) {
            return [
                'id' => $member->id,
                'username' => $member->username,
                'pixels' => $member->pixels_placed ?? 0,
                'role' => $member->pivot->role ?? 'member',
            ];
        });

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
            'pixels' => $group->pixels,
            'members' => $members,
        ]);
    }

    public function leaveGroup()
    {
        $user = Auth::user();
        
        // Get the user's first group (current group)
        $group = $user->groups()->first();
        
        if ($group) {
            $user->groups()->detach($group->id);
        }

        return response()->json(['message' => 'Left group successfully'], 200);
    }


    public function joinGroup(Request $request)
    {
        $request->validate([
            'invite_code' => 'required|string|exists:groups,invite_code',
        ]);

        $user = Auth::user();
        $group = Groups::where('invite_code', $request->input('invite_code'))->first();

        if (!$group) {
            return response()->json(['error' => 'Group not found'], 404);
        }

        // Check if user is already a member
        if ($user->groups()->where('groups.id', $group->id)->exists()) {
            return response()->json(['error' => 'You are already a member of this group'], 400);
        }

        // Attach user to group with default 'member' role
        $user->groups()->attach($group->id, ['role' => 'member']);

        return response()->json(['message' => 'You joined ' . $group->name . '.'], 200);
    }

}
