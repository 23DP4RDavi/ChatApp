<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupInvite extends Model
{
    protected $fillable = ['conversation_id', 'created_by', 'token', 'expires_at', 'max_uses', 'uses'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isFull(): bool
    {
        return $this->max_uses && $this->uses >= $this->max_uses;
    }
}
