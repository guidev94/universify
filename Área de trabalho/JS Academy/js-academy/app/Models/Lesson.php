<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['track_id', 'title', 'slug', 'content', 'task', 'starter_code', 'solution_code', 'xp_reward', 'order', 'tips', 'examples', 'real_world_case', 'docs_link'];

    public function track()
    {
        return $this->belongsTo(Track::class);
    }
}
