<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drawing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'drawing_data',
        'thumbnail',
        'votes_count',
        'theme_id',
        'is_free',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['creator_name', 'artist_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function comments()
    {
        return $this->hasMany(DrawingComment::class);
    }

    public function theme()
    {
        return $this->belongsTo(\App\Models\WeeklyTheme::class, 'theme_id');
    }

    public function getCreatorNameAttribute()
    {
        return $this->user?->name ?? 'Unknown';
    }

    public function getArtistNameAttribute()
    {
        return $this->creator_name;
    }
}
