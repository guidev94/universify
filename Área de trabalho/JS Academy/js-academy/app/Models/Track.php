<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function badge()
    {
        return $this->hasOne(Badge::class);
    }
}
