<?php

namespace App\Services;

use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PixelCounterService
{

    protected CountryIsoService $countryIsoService;

    public function __construct(CountryIsoService $countryIsoService)
    {
        $this->countryIsoService = $countryIsoService;
    }

    public static function addPixel(?User $user)
    {

        DB::transaction(function () use ($user) {

            $countryCode = null;

            $ip = request()->ip();

            if ($user) {
                if (!$user->country) {
                    $countryCode = app(CountryIsoService::class)->getCountries($ip);
                    if ($countryCode) {
                        $user->country = $countryCode;
                        $user->save();
                    }
                    else {
                        return null;
                    }
                }

                $countryCode = $user->country;

                $user->increment('pixels_placed');

                if ($groupMember = $user->groupMember) {
                    $groupMember->increment('pixels_placed');
                    $groupMember->group->increment('pixels');
                }
                else {
                    $countryCode = $this->countryIsoService->getCountries($ip);

                    if (!$countryCode) {
                        return;
                    }
                }
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
