<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupChannel extends Model
{
    protected $fillable = ['conversation_id', 'name', 'type', 'category', 'position', 'allowed_role_ids'];

    protected $casts = [
        'allowed_role_ids' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'channel_id');
    }
}
