<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'country',
        'is_admin',
        'email_verification_code',
        'email_verification_code_expires_at',
        'group_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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

    public function groups()
    {
        return $this->belongsToMany(Groups::class, 'group_members', 'user_id', 'group_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedGroup()
    {
        return $this->hasOne(Groups::class, 'owner_id');
    }
    
    // Get the user's primary/current group (first group they're a member of)
    public function group()
    {
        return $this->groups()->first();
    }
}
