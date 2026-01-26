<?php

namespace App\Http\Controllers;

use App\Models\BannedUser;
use Illuminate\Http\Request;
use App\Models\Pixel;

class BanUserController extends Controller
{
    public function ban(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'visitor_id' => 'nullable|string',
            'ip_address' => 'nullable|string',
            'reason' => 'required|string|max:500',
            'hide_pixels' => 'boolean',
            'is_permanent' => 'boolean',
            'banned_until' => 'nullable|date',
        ]);

        // Create banned user record
        $bannedUser = BannedUser::create([
            'user_id' => $validated['user_id'] ?? null,
            'visitor_id' => $validated['visitor_id'] ?? null,
            'ip_address' => $validated['ip_address'] ?? null,
            'reason' => $validated['reason'],
            'banned' => true,
            'hide_pixels' => $validated['hide_pixels'] ?? false,
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
}
