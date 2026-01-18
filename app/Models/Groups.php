<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Groups extends Model
{

    protected $fillable = [
        'name',
        'owner_id',
        'invite_code',
        'pixels',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }


    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function members()
    {
        return $this->hasMany(User::class, 'group_id')
            ->select('id', 'username', 'group_id', 'group_pixels');
    }
}
