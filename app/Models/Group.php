<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
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


    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id')
            ->withPivot('role', 'pixels_placed')
            ->withTimestamps()
            ->select('users.id', 'users.username')
            ->orderBy('group_members.pixels_placed', 'desc');
    }
}
