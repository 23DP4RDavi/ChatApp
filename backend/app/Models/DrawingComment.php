<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrawingComment extends Model
{
    protected $fillable = ['drawing_id', 'user_id', 'content'];

    public function drawing()
    {
        return $this->belongsTo(Drawing::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
