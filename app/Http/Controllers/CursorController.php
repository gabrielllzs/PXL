<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\GroupCursorMoved;

class CursorController extends Controller
{
    public function move(Request $request)
    {
        $request->validate([
            'x' => 'required|integer',
            'y' => 'required|integer',
        ]);

        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $group = $user->group ?? null;
        if (!$group) {
            return response()->json(['error' => 'Not in a group'], 400);
        }

        event(new GroupCursorMoved(
            $request->input('x'),
            $request->input('y'),
            $user->username,
            $group->id
        ));

        return response()->json(['success' => true]);
    }
}
