<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyTheme extends Model
{
    protected $fillable = [
        'week_number',
        'year',
        'theme_name',
        'description',
        'emoji',
        'color_hex',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at'   => 'date',
    ];
}
