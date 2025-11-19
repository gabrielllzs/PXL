<?php

namespace App\Http\Controllers;

use App\Models\Pixel;
use Illuminate\Http\Request;

class PixelController extends Controller
{
    public function index()
    {
        return Pixel::all();
    }

    public function store(Request $request)
    {
        $visitorId = $request->input('visitorId');
        $cooldownSeconds = 60;

        $request->validate([
            'i' => 'required|integer',
            'j' => 'required|integer',
            'color' => 'required|string',
            'visitorId' => 'required|string'
        ]);

        $ip = $request->ip();

        // Check cooldown by visitorId
        $lastPixel = Pixel::where('visitor_id', $visitorId)
            ->where('created_at', '>', now()->subSeconds($cooldownSeconds))
            ->latest()
            ->first();

        if ($lastPixel) {
            $elapsed = now()->diffInSeconds($lastPixel->created_at);
            return response()->json([
                'error' => 'cooldown',
                'remaining' => round($cooldownSeconds + $elapsed),
            ], 429);
        }

        $pixel = Pixel::updateOrCreate(
            [
                'i' => $request->i,
                'j' => $request->j,
            ],
            [
                'color' => $request->color ?? 'black',
                'visitor_id' => $visitorId,
                'ip' => $ip
            ]
        );

        return response()->json($pixel);
    }
}
