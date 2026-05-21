<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupRole extends Model
{
    protected $fillable = ['conversation_id', 'name', 'color', 'permissions', 'position', 'is_default'];

    protected $casts = [
        'permissions' => 'array',
        'is_default' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function memberRoles()
    {
        return $this->hasMany(GroupMemberRole::class, 'role_id');
    }
}
