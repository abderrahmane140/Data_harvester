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

        if ($lessons->isEmpty()) {
            $dataItems = Data::where('course_id', $courseId)
                           ->whereNull('lesson_id')
                           ->get();
            
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
    // Special case for "الامتحان"
    if (request()->has('type') && request()->type === 'الامتحان') {
            $dataItems = Data::where('title', 'LIKE', '%الامتحان%')
                                ->whereNull('lesson_id')
                                ->get();
        
        return response()->json($dataItems);
    }

    $query = Data::where('lesson_id', $lessonId);

    // Apply type filter with bilingual handling
    if (request()->has('type') && request()->type) {
        $type = request()->type;
        
        // Map of Arabic to French equivalents
        $typeMap = [
            'دروس' => ['Coure', 'cours'],
            'فروض' => ['exam', 'examen', 'devoir'],
            'تمارين' => ['exercice', 'exercise'],
            'ملخصات' => ['résumé', 'resume', 'summary'],
            'فيديو' => ['video', 'vidéo']
        ];

        // Find all equivalent types
        $typesToSearch = [$type];
        foreach ($typeMap as $arabic => $french) {
            if ($arabic === $type || in_array($type, $french)) {
                $typesToSearch = array_merge([$arabic], $french);
                break;
            }
        }
        
        $query->whereIn('value', $typesToSearch);
    }

    return response()->json($query->get());
}
}