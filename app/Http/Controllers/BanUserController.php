<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pixel;

class BanUserController extends Controller
{
    public function ban(Request $request, $visitor_id, $userIp)
    {
        $request->validate([
            'is_permanent' => 'boolean',
            'reason' => 'required|string|max:255',
        ]);

        // Find the user by ID
        $user = Pixel::find($visitor_id, $userIp);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->banned = true;
        $user->ban_reason = $request->input('reason');
        $user->save();

        return response()->json(['message' => 'User banned successfully'], 200);
    }

    public function getBannedMetrics()
    {

        $now = now();

        $total_records = Pixel::count();
        $active_banned_count = Pixel::where('banned', true)->count();
        $permanent_count = Pixel::where('is_permanent', true)->count();
        $temporary_count = Pixel::where('is_permanent', false)
            ->whereNotNull('banned_until')
            ->where('banned_until', '>', $now)
            ->count();


        $metrics = [
            'current'   => $active_banned_count,
            'permanent' => $permanent_count,
            'temporary' => $temporary_count,
            'total'     => $total_records
        ];


        return response()->json($metrics, 200);

    }
}
