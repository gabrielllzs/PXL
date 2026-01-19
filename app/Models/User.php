<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'country',
        'is_admin',
        'email_verification_code',
        'email_verification_code_expires_at',
        'pixels_placed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function pixels()
    {
        return $this->hasMany(Pixel::class);
    }

    public function groupMember()
    {
        return $this->hasOne(GroupMembers::class, 'user_id');
    }

    public function group()
    {
        return $this->hasOneThrough(
            Groups::class, GroupMembers::class, 'user_id', 'id', 'id', 'group_id'
        );
    }

    public function ownedGroup()
    {
        return $this->hasOne(Groups::class, 'owner_id');
    }

    public function countryRelation()
    {
        return $this->belongsTo(Country::class, 'country', 'country_code');
    }

}
