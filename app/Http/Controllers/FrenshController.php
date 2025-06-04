<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Level;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Data;
use Illuminate\Support\Str;

class FrenshController extends Controller
{
    public function french(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid or missing URL.'], 400);
        } 

        $url = $request->input('url');

        try {
            $response = Http::get($url);
            if (!$response->ok()) {
                return response()->json([
                    'error' => 'Failed to retrieve the page.',
                    'status' => $response->status()
                ], 500);
            }

            $html = $response->body();
            $crawler = new Crawler($html);
            $content = [];

            // Extract title
            $titleNode = $crawler->filter('h1.entry-title')->first();
            $title = $titleNode->count() ? trim($titleNode->text()) : '';

            // Extract paragraphs and .mada links
            $crawler->filter('div.inside-article')->filter('p, .mada a')->each(function ($node) use (&$content) {
                $text = trim($node->text());
                if (!empty($text)) {
                    $content[] = $text;
                }
            });


        $levels = ['الثانية باك', 'اولى باك','جذع مشترك', 'الثالثة اعدادي', 'الثانية اعدادي', 'الاولى اعدادي', 'السادس ابتدائي', 'الخامس ابتدائي', 'الرابع ابتدائي', 'الثالث ابتدائي', 'الثاني ابتدائي', 'الاول ابتدائي'];
            $types = ['دروس', 'فروض', ' وامتحانات', 'امتحانات', 'ملخصات', 'تمارين', 'علمي و اداب'];
            $subjects = [
                'التاريخ و الجغرافيا','علوم الحياة والارض',
                'الرياضيات – اداب','علوم الحياة والارض – اداب',
                'الفيزياء والكيمياء خيار فرنسية','الرياضيات خيار فرنسية',
                'الرياضيات', 'الفيزياء والكيمياء', 'علوم الحياة والارض', 'اللغة الانجليزية', 'اللغة الفرنسية',
                'اللغة العربية', 'الفلسفة', 'التاريخ والجغرافيا', 'علوم المهندس', 'القانون',
                'المحاسبة والرياضيات المالية', 'الاقتصاد والتنظيم الاداري للمقاولات', 'الإقتصاد العام والإحصاء',
                'معلوميات التدبير', 'الفقه والاصول', 'التربية الاسلامية','النشاط العلمي','الاجتماعيات','التربية السلامية','التكنولوجيا الصناعية',
            ];
        
        $normalizedTitle = preg_replace('/\s+/', ' ', trim($title));
        $normalizedTitle = str_replace( 'جدع مشترك', 'جذع مشترك', $normalizedTitle);
        $normalizedTitle = str_replace('الأولى اعدادي', 'الاولى اعدادي', $normalizedTitle);


        $matchedLevel = collect($levels)->first(fn($lvl) => mb_strpos($normalizedTitle, $lvl) !== false);

    // Find level in DB
    $level = $matchedLevel
        ? Level::where('name', 'like', '%' . $matchedLevel . '%')->first()
        : null;

    //dd($matchedLevel, $normalizedTitle, $level);

    if (!$level) {
        return response()->json(['error' => 'No matching level found in the title.'], 400);
    }
    $matchLesson = str_replace($levels, '', $normalizedTitle);
    $types = ['دروس','درس', 'فروض', 'امتحانات', 'ملخصات','ملخص','وتمارين','و تمارين','2024-2025 مع التصحيح','ابتدائي ','دولي','تمارين','مسلك','علمي و اداب','علمي وتكنولوجي','اداب وعلوم انسانية','(علوم) وتكنولوجي','علمي','آداب وعلوم إنسانية','وتكنولوجية','و ','مع التصحيح','وطنية مادة','جهوية في ','محلية في','اقليمية في','تكنولوجي'];
    foreach($types as $type) {
        $matchLesson = str_replace($type, '', $matchLesson);
    }
    $matchLesson = trim($matchLesson);
      if($matchLesson === 'الفيزياء والكيمياء  علوم خيار فرنسية'){
        $matchLesson = 'الفيزياء والكيمياء  خيار فرنسية علوم الحياة والارض';
    }
    if($matchLesson === 'الفيزياء والكيمياء   وعلوم رياضية خيار فرنسية'){
        $matchLesson = 'الفيزياء والكيمياء علوم رياضية خيار فرنسية';
    }
    $lesson = Lesson::where('title', $matchLesson)
        ->whereHas('course', function ($query) use ($level) {
            $query->where('level_id', $level->id);
        })
    ->first();    
    

  

    $lessonId = optional($lesson)->id;

    $courseId = $lesson ? $lesson->course_id : null; 


    // If lesson not found, try matching course directly
    if (!$lessonId) {
        $course = Course::where('name', $matchLesson )
            ->where('level_id', $level->id)
            ->first();

        if ($course) {
            $courseId = $course->id;
        }
    }
    //dd($matchLesson, $matchedLevel,$lessonId, $courseId, $level->id);
    if (!$courseId) {
    $course = Course::where('level_id', $level->id)->first();
    if (!$course) {
        return response()->json(['error' => 'No matching course found for the level.'], 400);
    }

}
    $levelId = $level->id;

    $crawler->filter('table')->each(function ($table) use ($level, $course, $url) {
    $headers = [];

    // Get <th> headers as values (e.g., 'Cours', 'Vidéos')
    $table->filter('tr')->first()->filter('th')->each(function ($th, $i) use (&$headers) {
        $headers[$i] = trim($th->text());
    });

    // Iterate each table row (skip the first one which is headers)
    $table->filter('tr')->each(function ($tr, $rowIndex) use ($headers, $level, $course, $url) {
        $tds = $tr->filter('td');

        if ($tds->count()) {
            $lessonTitle = trim($tds->eq(0)->text()); // First column is always lesson title

            // Create lesson once
            $lesson = Lesson::firstOrCreate(
                ['title' => $lessonTitle, 'course_id' => $course->id, 'url' => $url],
                ['slug' => Str::slug($lessonTitle), 'created_at' => now()]
            );

            // Loop through the rest of the <td> elements and map to headers
            foreach ($tds as $i => $td) {
                if ($i === 0) continue; // Skip the lesson title column

                $aTag = (new \Symfony\Component\DomCrawler\Crawler($td))->filter('a');

                if ($aTag->count()) {
                    $link = $aTag->attr('href');
                    $title = $headers[$i] ?? 'Ressource';

                    Data::create([
                        'lesson_id' => $lesson->id,
                        'level_id' => $level->id,
                        'course_id' => $course->id,
                        'title' => $lessonTitle,
                        'value' => $title,
                        'url' => $link,
                    ]);
                }
            }
        }
    });
});



    
    echo "slug: $normalizedTitle\n";
    echo "matched level: $level\n";
    echo "lessonid: $lessonId, levelid: $levelId, courseid: $courseId\n";

        } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to scrape the page.',
                    'details' => $e->getMessage()
                ], 500);
            }
    }
}
