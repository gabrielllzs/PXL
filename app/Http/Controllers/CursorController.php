<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\GroupCursorMoved;

class CursorController extends Controller
{
    public function move(Request $request)
    {
        // Ultra-cheap validation
        $x = (int) $request->input('x');
        $y = (int) $request->input('y');

        if ($x === null || $y === null) {
            return response()->noContent(422);
        }

        $user = $request->user();
        if (!$user || !$user->group_id) {
            return response()->noContent(403);
        }

        broadcast(new GroupCursorMoved(
            $x,
            $y,
            $user->username,
            $user->group_id
        ))->toOthers();

        // No JSON, no serialization
        return response()->noContent();
    }
}
