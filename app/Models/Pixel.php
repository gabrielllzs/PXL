<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Pixel extends Model
{
    protected $fillable = ['x', 'y', 'color', 'visitor_id', 'user_id', 'ip_address', 'hidden'];

    protected $casts = [
        'hidden' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
