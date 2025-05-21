<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    // Liste de toutes les leçons
    public function index()
    {
        $lessons = Lesson::all();
        return response()->json($lessons);
    }

    // Récupérer une leçon spécifique
    public function show($id)
    {
        $lesson = Lesson::findOrFail($id);
        return response()->json($lesson);
    }

    // Ajouter une nouvelle leçon
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'nullable|url',
            'course_id' => 'required|exists:courses,id',
        ]);

        $lesson = Lesson::create($request->all());
        return response()->json($lesson, 201);
    }

    // Modifier une leçon
    public function update(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);

        $lesson->update($request->all());
        return response()->json($lesson);
    }

    // Supprimer une leçon
    public function destroy($id)
    {
        Lesson::destroy($id);
        return response()->json(null, 204);
    }
    
    // ----
    public function getContents($id)
{
    $lesson = Lesson::with(['resources'])->find($id);

    $grouped = [
        'course' => [],
        'summary' => [],
        'exercise' => [],
        'exam' => [],
        'sheet' => [],
        'video' => []
    ];

    foreach ($lesson->resources as $resource) {
        $grouped[$resource->type][] = [
            'title' => $resource->title,
            'url' => $resource->url
        ];
    }

    return response()->json($grouped);
}
}


