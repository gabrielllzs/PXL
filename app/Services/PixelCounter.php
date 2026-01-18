<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PixelCounter
{
    public static function addPixel(?User $user, ?string $country = null)
    {
        if (!$user) {
            // For visitors, we might track country later
            return;
        }

        DB::transaction(function () use ($user) {

            // + 1 to the user's pixels placed
            $user->increment('pixels_placed');

            // + 1 to the group's pixels placed
            if ($user->groupMember) {
                $user->groupMember->increment('pixels_placed');

                // + 1 Increment the group's total pixels
                $user->groupMember->group->increment('pixels');
            }

            //  later: country track
            // if ($country) {
            //     Cache::increment("pixels_country:$country");
            // }
        });
    }
}
