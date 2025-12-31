<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannedUser extends Model
{
    protected $fillable = [
        'visitor_id',
        'ip_address',
        'reason',
        'hide_pixels',
        'is_permanent',
        'banned_until',
        'banned',
    ];

    protected $casts = [
        'hide_pixels' => 'boolean',
        'is_permanent' => 'boolean',
        'banned_until' => 'datetime',
        'banned' => 'boolean',
    ];
}
