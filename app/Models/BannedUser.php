<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannedUser extends Model
{
    protected $fillable = [
        'visitor_id',
        'ip_address',
        'reason',
        'banned_until',
        'is_permanent'
    ];

    protected $casts = [
        'is_permanent' => 'boolean',
        'banned_at' => 'datetime',
    ];
}
