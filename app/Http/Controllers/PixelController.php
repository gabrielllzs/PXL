<?php

namespace App\Http\Controllers;

use App\Events\PixelPlaced;
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
            'x' => 'required|integer',
            'y' => 'required|integer',
            'color' => 'required|string',
            'visitorId' => 'required|string'
        ]);


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
                'x' => $request->x,
                'y' => $request->y,
            ],
            [
                'color' => $request->color ?? 'black',
                'visitor_id' => $visitorId,
            ]
        );

        event(new PixelPlaced($pixel->x, $pixel->y, $pixel->color));
        return response()->json($pixel);
    }

    public function cooldown(Request $request)
    {
        $visitorId = $request->input('visitorId');
        $cooldownSeconds = 60;

        $last = Pixel::where('visitor_id', $visitorId)
            ->latest()
            ->first();

        if (!$last) {
            return ['cooldown' => false, 'remaining' => 0];
        }

        $elapsed = $last->created_at->diffInSeconds(now());
        $remaining = max(0, $cooldownSeconds - $elapsed);

        return [
            'cooldown' => $remaining > 0,
            'remaining' => $remaining
        ];
    }
}
