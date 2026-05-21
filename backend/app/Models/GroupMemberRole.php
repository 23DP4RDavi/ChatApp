<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMemberRole extends Model
{
    protected $fillable = ['conversation_id', 'user_id', 'role_id'];

    public function role()
    {
        return $this->belongsTo(GroupRole::class, 'role_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
