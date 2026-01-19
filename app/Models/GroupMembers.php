<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMembers extends Model
{
    protected $table = 'group_members';

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'pixels_placed',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
