<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function showProfile()
    {
        $user = Auth::user();

        return response()->json([
            'username' => $user->username,
            'total_pixels' => $user->pixels_placed,
            'group' => $user->group ? [
                'name' => $user->group->name,
                'total_group_pixels' => $user->group->members()->sum('group_pixels'),
            ] : null,
        ]);
    }


    public function getUsersPixels()
    {
        $users = User::orderBy('pixels_placed', 'desc')
            ->take(10)
            ->get(['username', 'pixels_placed']);

        return response()->json($users
        );
    }
}
