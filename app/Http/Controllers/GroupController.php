<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\GroupMembers;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function index()
    {
        // leaderboard
        $groups = Group::get();

        return response()->json(
            $groups->map(function ($group) {
                return [
                    'name' => $group->name,
                    'pixels' => $group->pixels,
                ];
            })->sortByDesc('pixels')->values()
        );
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:16', 'unique:groups,name'],
        ]);

        $user = Auth::user();

        // user already in a group
        if ($user->groupMember) {
            return response()->json(['error' => 'You are already in a group'], 400);
        }

        $group = Group::create([
            'name' => $validated['name'],
            'owner_id' => $user->id,
            'invite_code' => bin2hex(random_bytes(6)),
        ]);

        GroupMembers::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
            'pixels' => $group->pixels,
            'invite_code' => $group->invite_code,
        ], 201);
    }

    public function showMyGroup()
    {
        $user = Auth::user();

        $group = $user->group;

        if (!$group) {
            return response()->json(null, 404);
        }

        $members = $group->members()->get()->map(function ($member) {
            return [
                'username' => $member->username,
                'pixels' => $member->pivot->pixels_placed ?? 0,
                'role' => $member->pivot->role,
            ];
        });

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
            'pixels' => $group->pixels,
            'members' => $members,
            'invite_code' => $group->invite_code,
        ]);
    }

    public function leaveGroup()
    {
        $user = Auth::user();

        if ($user->groupMember) {
            $user->groupMember->delete();
        }

        return response()->json(['message' => 'Left group successfully'], 200);
    }

    public function joinGroup(Request $request)
    {
        $request->validate([
            'invite_code' => 'required|string|exists:groups,invite_code',
        ]);

        $user = Auth::user();

        if ($user->groupMember) {
            return response()->json(['error' => 'You are already in a group'], 400);
        }

        $group = Group::where('invite_code', $request->invite_code)->firstOrFail();

        GroupMembers::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);

        return response()->json(['message' => 'You joined ' . $group->name], 200);
    }
}
