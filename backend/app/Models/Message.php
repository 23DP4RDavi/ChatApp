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
        'channel_id',
        'content',
        'drawing_data',
        'reply_to_id',
        'reactions',
        'is_pinned',
        'edited_at',
    ];

    protected $casts = [
        'drawing_data' => 'array',
        'reactions' => 'array',
        'is_pinned' => 'boolean',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id')->with('user');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function channel()
    {
        return $this->belongsTo(GroupChannel::class, 'channel_id');
    }
}
