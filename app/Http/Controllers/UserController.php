<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use App\Services\LevelService;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected LevelService $levelService;

    public function __construct(LevelService $levelService)
    {
        $this->levelService = $levelService;
    }

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
            ->take(15)
            ->get(['username', 'pixels_placed', 'level']);

        return response()->json($users
        );
    }

    public function getPixelStatus()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->refresh();
        $this->levelService->updateUserLevel($user);
        $user->refresh();

        $pixelLimit = $this->levelService->getPixelLimit($user->level);
        $regenerationTime = $this->levelService->getRegenerationTime($user->level);
        $timeUntilRegen = $this->levelService->getTimeUntilRegeneration($user);
        $levelProgress = $this->levelService->getLevelProgress($user);

        return response()->json([
            'level' => $user->level,
            'pixels_available' => $user->pixels_available,
            'pixel_limit' => $pixelLimit,
            'regeneration_time' => $regenerationTime,
            'time_until_regeneration' => $timeUntilRegen,
            'level_progress' => $levelProgress,
            'total_pixels_placed' => $user->pixels_placed,
        ]);
    }
    public function getCurrentUser()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(null);
        }

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email, // Needed for verification UI, safe as it's user's own data
            'level' => $user->level,
            'pixels_placed' => $user->pixels_placed,
            'email_verified' => $user->email_verified,
            'country' => $user->country,
        ]);
    }
}
