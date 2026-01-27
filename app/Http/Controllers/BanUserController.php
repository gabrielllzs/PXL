<?php

namespace App\Http\Controllers;

use App\Models\BannedUser;
use App\Models\Pixel;
use App\Models\User;
use Illuminate\Http\Request;

class BanUserController extends Controller
{
    public function ban(Request $request)
    {


        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'visitor_id' => 'nullable|string',
            'ip_address' => 'nullable|string',
            'reason' => 'required|string|max:500',
            // 'hide_pixels' => 'boolean',
            'is_permanent' => 'boolean',
            'banned_until' => 'nullable|date',
        ]);

        $visitorId = $validated['visitor_id'] ?? null;
        if ($validated['user_id'] && !$visitorId) {
            $user = User::find($validated['user_id']);
            if ($user && $user->visitor_id) {
                $visitorId = $user->visitor_id;
            }
        }

        // Create banned user record
        $bannedUser = BannedUser::create([
            'user_id' => $validated['user_id'] ?? null,
            'visitor_id' => $visitorId,
            'ip_address' => $validated['ip_address'] ?? null,
            'reason' => $validated['reason'],
            'banned' => true,
            // 'hide_pixels' => $validated['hide_pixels'] ?? false,
            'is_permanent' => $validated['is_permanent'] ?? false,
            'banned_until' => $validated['is_permanent'] ? null : $validated['banned_until'],
        ]);

        return response()->json([
            'message' => 'User banned successfully',
            'banned_user' => $bannedUser
        ]);
    }

    public function getBannedVisitors()
    {
        $bannedVisitors = BannedUser::with('user:id,username,email')
            ->where('banned', true)
            ->get();

        return response()->json($bannedVisitors, 200);
    }

    public function unban(Request $request, $id)
    {
        $bannedUser = BannedUser::findOrFail($id);
        
        $bannedUser->banned = false;
        $bannedUser->is_permanent = false;
        $bannedUser->save();

        return response()->json([
            'message' => 'User unbanned successfully',
            'banned_user' => $bannedUser
        ]);
    }

    public function getUsers()
    {
        // Get all user IDs that have active bans
        $bannedUserIds = BannedUser::where('banned', true)
            ->where(function ($query) {
                $query->where('is_permanent', true)
                    ->orWhere(function ($q) {
                        $q->whereNotNull('banned_until')
                            ->where('banned_until', '>', now());
                    });
            })
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->toArray();

        // Get all visitor IDs that have active bans
        $bannedVisitorIds = BannedUser::where('banned', true)
            ->where(function ($query) {
                $query->where('is_permanent', true)
                    ->orWhere(function ($q) {
                        $q->whereNotNull('banned_until')
                            ->where('banned_until', '>', now());
                    });
            })
            ->whereNotNull('visitor_id')
            ->pluck('visitor_id')
            ->toArray();

        // Get users excluding those with active bans
        $users = User::select('id', 'username', 'email', 'visitor_id')
            ->whereNotIn('id', $bannedUserIds)
            ->get();

        // Get anonymous visitors excluding those with active bans
        $anonymousVisitors = Pixel::query()
            ->whereNull('user_id')
            ->whereNotIn('visitor_id', $bannedVisitorIds)
            ->orderBy('id', 'desc')
            ->get()
            ->unique('visitor_id')
            ->values();

        return [
            'users' => $users,
            'anonymous_visitors' => $anonymousVisitors,
        ];
    }
}
