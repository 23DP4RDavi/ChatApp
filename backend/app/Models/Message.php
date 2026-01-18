<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'content',
        'drawing_data',
    ];

    protected $casts = [
        'drawing_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['user'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
