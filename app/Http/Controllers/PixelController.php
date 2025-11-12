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
        $request->validate([
            'i' => 'required|integer',
            'j' => 'required|integer',
            'color' => 'string'
        ]);

        $pixel = Pixel::updateOrCreate(
            ['i' => $request->i, 'j' => $request->j],
            ['color' => $request->color ?? 'red']
        );

        return response()->json($pixel);
    }
}
