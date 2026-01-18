<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drawing extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'guest_name',
        'title',
        'drawing_data',
        'thumbnail',
        'votes_count',
    ];

    /**
     * Get the user that owns the drawing (if registered user).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all votes for this drawing.
     */
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Get the creator name (user or guest).
     */
    public function getCreatorNameAttribute()
    {
        return $this->user ? $this->user->name : ($this->guest_name ?? 'Anonymous');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['creator_name'];
}
