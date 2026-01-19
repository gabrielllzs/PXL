<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::orderBy('pixels_placed', 'desc')->take(10)->get(['country_code', 'pixels_placed']);
        return response()->json($countries, 200);
    }
}
