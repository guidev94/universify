<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = ['name', 'icon', 'description', 'track_id'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }
}
