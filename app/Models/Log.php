<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'log';

    protected $fillable = [
        'message',
        'channel',
        'level',
        'level_name',
        'unix_time',
        'datetime',
        'context',
        'extra',
    ];

    protected $casts = [
        'context' => 'json',
        'extra' => 'json',
        'unix_time' => 'integer',
        'level' => 'integer',
    ];
}
