<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function exercises()
    {
        return $this->hasMany(Exercise::class);
    }
        protected $fillable = ['level_id', 'name', 'url'];

}
