<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    protected $fillable = ['page', 'user_id', 'ip', 'user_agent', 'referer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
