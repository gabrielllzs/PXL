<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;

class CountryController extends Controller
{
    public function index()
    {

        $countries = Country::orderBy('pixels_placed', 'desc')->take(10)->get(['country_code', 'pixels_placed']);

        $refactoredCountries = $countries->map(function ($country) {
            $countryName = country($country->country_code);

            return [
                'country' => $countryName ? $countryName->getName() : $country->country_code ,
                'pixels' => $country->pixels_placed,
                'emoji' => $countryName->getEmoji(),
            ];
        });

        return response()->json($refactoredCountries, 200);
    }
}
