<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Pixel extends Model
{
    protected $fillable = ['x', 'y', 'color', 'visitor_id', 'fingerprint_components' , 'risk_score'];

    protected $casts = [
        'fingerprint_components' => 'array',
        'risk_score' => 'integer',
    ];
}
