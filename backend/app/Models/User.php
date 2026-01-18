<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the drawings created by this user.
     */
    public function drawings()
    {
        return $this->hasMany(Drawing::class);
    }

    /**
     * Get the messages sent by this user.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the votes cast by this user.
     */
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Get friendships where this user is the requester.
     */
    public function friendshipsRequested()
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    /**
     * Get friendships where this user is the receiver.
     */
    public function friendshipsReceived()
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    /**
     * Get conversations this user is participating in.
     */
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants');
    }

    /**
     * Update the user's last activity timestamp.
     */
    public function updateLastActivity()
    {
        $this->touch();
    }
}
