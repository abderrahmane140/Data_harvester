<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DetailsController extends Controller
{
    public function show($courseId)
    {
        $data = Data::where('course_id', $courseId)->get();

        $grouped = [
            'lesson' => [],
            'summary' => [],
            'exercise' => [],
            'exam' => [],
            'sheet' => [],
            'video' => [],
        ];

        foreach ($data as $item) {
            if (isset($grouped[$item->value])) {
                $grouped[$item->value][] = [
                    'title' => $item->title,
                    'url' => $item->url,
                ];
            }
        }

        return response()->json($grouped);
    }
}
