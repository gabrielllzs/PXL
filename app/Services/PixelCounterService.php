<?php

namespace App\Services;

use App\Models\Country;
use App\Models\User;
use App\Services\LevelService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PixelCounterService
{

    protected CountryIsoService $countryIsoService;
    protected LevelService $levelService;

    public function __construct(CountryIsoService $countryIsoService, LevelService $levelService)
    {
        $this->countryIsoService = $countryIsoService;
        $this->levelService = $levelService;
    }

    public function addPixel(?User $user, ?string $ip = null)
    {

        Log::info('Adding pixel for user ID: ' . ($user ? $user->id : 'guest') . ' with IP: ' . ($ip ?? 'unknown'));

        DB::transaction(function () use ($user, $ip) {

            $countryCode = null;

            if (!$ip) {
                $ip = request()->ip();
            }

            if ($user) {
                if (!$user->country && $ip) {
                    $countryCode = $this->countryIsoService->getCountries($ip);
                    if ($countryCode) {
                        $user->country = $countryCode;
                        $user->save();
                    }
                } else {
                    $countryCode = $user->country;
                }

                $user->increment('pixels_placed');
                
                // Update level (pixels_available is handled in PixelController)
                $this->levelService->updateUserLevel($user);

                if ($groupMember = $user->groupMember) {
                    $groupMember->increment('pixels_placed');
                    $groupMember->group->increment('pixels');
                }
            }
            if (!$countryCode && $ip) {
                $countryCode = $this->countryIsoService->getCountries($ip);
            }

            if ($countryCode) {
                $country = Country::firstOrCreate(
                    ['country_code' => $countryCode],
                    ['pixels_placed' => 0]
                );
                $country->increment('pixels_placed');
            }
        });
    }
}
