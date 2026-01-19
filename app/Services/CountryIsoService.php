<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CountryIsoService
{
    public function getCountries(string $ip): ?string
    {
        $countryIso = Http::get("https://api.country.is/{$ip}");
        return $countryIso->ok() ? $countryIso->json('country') : null;
    }
}
