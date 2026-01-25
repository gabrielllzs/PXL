<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PixelHistory extends Model
{
    protected $table = 'pixel_history';

    protected $fillable = [
        'x',
        'y',
        'color',
    ];
}
