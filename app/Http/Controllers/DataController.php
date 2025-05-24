<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Level;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Data;


class DataController extends Controller
{
    public function index(){
        $levels = Level::all();
        $courses = Course::all();
        $lessons = Lesson::all();
        $data = Data::all();
        $headers = ['دروس', 'ملخصات', 'تمارين', 'فروض', 'فيديوهات'];

        return view('data.index', compact('levels', 'courses', 'lessons', 'data', 'headers')); 
    }
     public function getCourses($levelId)
    {
        $courses = Course::where('level_id', $levelId)->get();
        return response()->json($courses);
    }
public function getLessons($courseId)
{
    $lessons = Lesson::where('course_id', $courseId)->get();
    if ($lessons->isEmpty()) {
        $dataItems = Data::where('course_id', $courseId)->get();
        return response()->json([
            'type' => 'data',
            'items' => $dataItems
        ]);
    }
    return response()->json([
        'type' => 'lessons',
        'items' => $lessons
    ]);
}


    public function getData($lessonId)
    {
        $dataItems = Data::where('lesson_id', $lessonId)->get();
        return response()->json($dataItems);
    }
}