<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedLocation extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'share_key',
        'longitude',
        'latitude',
        'zoom',
    ];

    protected $casts = [
        'longitude' => 'decimal:7',
        'latitude' => 'decimal:7',
        'zoom' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
