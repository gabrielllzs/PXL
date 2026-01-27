<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'country';

    protected $fillable = [
        'country_code',
        'pixels_placed',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'country', 'country_code');
    }
}
