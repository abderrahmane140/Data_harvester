<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Level;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Exercise;

class DataDisplayController extends Controller
{
    public function index()
    {
        $levels = Level::all();
        return view('data.index', compact('levels'));
    }

    public function getCourses($level_id)
    {
        return Course::where('level_id', $level_id)->get();
    }

    public function getDetails($course_id)
    {
        $lessons = Lesson::where('course_id', $course_id)->get();
        $exercises = Exercise::where('course_id', $course_id)->get();

        return response()->json([
            'lessons' => $lessons,
            'exercises' => $exercises,
        ]);
    }
}

