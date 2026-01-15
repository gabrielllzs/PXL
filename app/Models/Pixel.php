<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Pixel extends Model
{
    protected $fillable = ['x', 'y', 'color', 'visitor_id', 'user_id', 'risk_score', 'ip_address'];

    protected $casts = [
        'risk_score' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
