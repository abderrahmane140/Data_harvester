<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_id',
        'course_id',
        'lesson_id',
        'exercise_id',
        'title',
        'url',
        'value',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
    public function level()
    {
        return $this->belongsTo(Level::class);
    }
    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}