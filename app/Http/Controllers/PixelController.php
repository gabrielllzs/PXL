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

        $request->validate([
            'x' => 'required|integer',
            'y' => 'required|integer',
            'color' => 'required|string',
            'visitorId' => 'required|string'
        ]);

        $visitorId = $request->input('visitorId');

        $remaining = $this->cooldown($request)['remaining'];
        if ($remaining > 0) {
            return response()->json([
                'error' => 'cooldown',
                'remaining' => $remaining,
            ], 412);
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
