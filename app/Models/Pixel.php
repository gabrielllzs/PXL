<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Pixel extends Model
{
    protected $fillable = ['x', 'y', 'color', 'visitor_id', 'risk_score', 'ip_address'];

    protected $casts = [
        'risk_score' => 'integer',
    ];
}
