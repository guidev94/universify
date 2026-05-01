<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogicChallenge extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category',
        'description',
        'task',
        'starter_code',
        'xp_reward',
        'diamond_reward',
        'difficulty',
    ];
}
