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
        'new_email',
        'password',
        'country',
        'is_admin',
        'email_verification_code',
        'email_verification_code_expires_at',
        'email_verified',
        'pixels_placed',
        'level',
        'pixels_available',
        'last_pixel_regeneration_time',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code',
        'email_verification_code_expires_at',
        'email_verified_at',
        'new_email',
        'is_admin',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verified' => 'boolean',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'last_pixel_regeneration_time' => 'datetime',
        ];
    }


    /**
     * Get the attributes that should be visible in the model's array form.
     * Only show is_admin if the current authenticated user is an admin.
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        if (auth()->check() && auth()->user()->isAdmin()) {
            $array['is_admin'] = $this->is_admin;
        }

        return $array;
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
            Group::class, GroupMembers::class, 'user_id', 'id', 'id', 'group_id'
        );
    }

    public function ownedGroup()
    {
        return $this->hasOne(Group::class, 'owner_id');
    }

    public function countryRelation()
    {
        return $this->belongsTo(Country::class, 'country', 'country_code');
    }

}
