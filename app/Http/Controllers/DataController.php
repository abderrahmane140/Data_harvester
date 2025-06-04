<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Level;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Data;

class DataController extends Controller
{
    public function index()
    {
        $levels = Level::all();
        return view('data.index', compact('levels'));
    }

    public function getCourses($levelId)
    {
        $courses = Course::where('level_id', $levelId)->get();
        return response()->json($courses);
    }

    public function getLessons($courseId)
    {
        $lessons = Lesson::where('course_id', $courseId)->get();


        return response()->json([
            'type' => 'lessons',
            'items' => $lessons
        ]);
    }

    public function getData($lessonId, $levelId, $courseId)
    {
        $query = Data::where('lesson_id', $lessonId)
                   ->where('level_id', $levelId)
                   ->where('course_id', $courseId);

        if (request()->has('type') && request()->type) {
            $type = request()->type;
            $query->where(function($q) use ($type) {
                $q->where('value', $type);
                
                // Also check for equivalent types
                $equivalents = $this->getTypeEquivalents($type);
                if (!empty($equivalents)) {
                    $q->orWhereIn('value', $equivalents);
                }
            });
        }

        return response()->json($query->get());
    }

    public function getExamData(Request $request)
    {
        $request->validate([
            'level_id' => 'required|integer',
            'course_id' => 'required|integer'
        ]);

        $exams = Data::where('level_id', $request->level_id)
                   ->where('course_id', $request->course_id)
                   ->where(function($query) {
                       $query->where('title', 'LIKE', '%الامتحان%')
                             ->orWhere('value', 'LIKE', '%الامتحان%');
                   })
                   ->get();

        return response()->json($exams);
    }

    protected function getTypeEquivalents($type)
    {
        $equivalents = [
            'دروس' => ['Coure', 'cours'],
            'فروض' => ['exam', 'examen', 'devoir'],
            'تمارين' => ['exercice', 'exercise'],
            'ملخصات' => ['résumé', 'resume', 'summary'],
            'فيديو' => ['video', 'vidéo']
        ];
        
        foreach ($equivalents as $arabic => $frenchTypes) {
            if ($arabic === $type || in_array($type, $frenchTypes)) {
                return array_merge([$arabic], $frenchTypes);
            }
        }
        
        return [$type];
    }
    public function getSpecialData(Request $request)
{
    $request->validate([
        'level_id' => 'required|integer',
        'course_id' => 'required|integer',
        'type' => 'required|string'  // Will be either 'الامتحان' or 'فروض'
    ]);

    $query = Data::where('level_id', $request->level_id)
               ->where('course_id', $request->course_id)
               ->whereNull('lesson_id'); // No lesson_id for these types

    if ($request->type === 'الامتحان') {
        $query->where(function($q) {
            $q->where('title', 'LIKE', '%الامتحان%')
              ->orWhere('value', 'LIKE', '%الامتحان%');
        });
    } else if ($request->type === 'فروض') {
        $query->where(function($q) {
            $q->where('title', 'LIKE', '%فروض%')
              ->orWhere('value', 'LIKE', '%فروض%')
              ->orWhereIn('value', ['exam', 'examen', 'devoir']); // French equivalents
        });
    }

    return response()->json($query->get());
}
}