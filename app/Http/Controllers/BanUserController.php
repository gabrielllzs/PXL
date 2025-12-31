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
            'visitor_id' => 'required|string',
            'ip_address' => 'required|string',
            'reason' => 'required|string|max:500',
            'hide_pixels' => 'boolean',
            'is_permanent' => 'boolean',
            'banned_until' => 'nullable|date',
        ]);

        // Create banned user record
        $bannedUser = BannedUser::create([
            'visitor_id' => $validated['visitor_id'],
            'ip_address' => $validated['ip_address'],
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

    public function getBannedVisitors()
    {
        $bannedVisitors = BannedUser::where('banned', true)->get();


        if ($bannedVisitors->isNotEmpty()) {
            return response()->json($bannedVisitors, 200);
        } else {
            return response()->json(['message' => 'No banned visitors found'], 200);
        }
    }
}
