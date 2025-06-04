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

class ScrapeEducationData extends Controller
{
    public function form()
    {
        return view('welcome');
    }

    public function scrape(Request $request)
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

            // Define reference data
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

        // Normalize title
        // Normalize title
        $normalizedTitle = preg_replace('/\s+/', ' ', trim($title));

        // Replace special cases
        $normalizedTitle = str_replace('المستوى الاول', 'الاول ابتدائي', $normalizedTitle);
        $normalizedTitle = str_replace('المستوى الثاني', 'الثاني ابتدائي', $normalizedTitle);
        $normalizedTitle = str_replace('المستوى الثالث', 'الثالث ابتدائي', $normalizedTitle);
        $normalizedTitle = str_replace('المستوى الرابع', 'الرابع ابتدائي', $normalizedTitle);
        $normalizedTitle = str_replace('المستوى الخامس', 'الخامس ابتدائي', $normalizedTitle);
        $normalizedTitle = str_replace('الخامسة ابتدائي', 'الخامس ابتدائي', $normalizedTitle);
        $normalizedTitle = str_replace('المستوى السادس', 'السادس ابتدائي', $normalizedTitle);
        $normalizedTitle = str_replace( 'الأولى اعدادي', 'الاولى اعدادي', $normalizedTitle);
        $normalizedTitle = str_replace( 'جذع مشترك', 'جذع مشترك', $normalizedTitle);
        $normalizedTitle = str_replace( 'جدع مشترك', 'جذع مشترك', $normalizedTitle);
        $normalizedTitle = str_replace( 'الثانية بكالوريا', 'الثانية باك', $normalizedTitle);
        $normalizedTitle = str_replace( 'الفقه والأصول', 'الفقه والاصول', $normalizedTitle);




        
        //$normalizedTitle = str_replace( ':', ' -', $normalizedTitle);



        // Match values
        $matchedLevel = collect($levels)->first(fn($lvl) => mb_strpos($normalizedTitle, $lvl) !== false);
        $matchedTypes = collect($types)->filter(fn($t) => mb_strpos($normalizedTitle, $t) !== false)->values()->all();
        $matchedSubject = collect($subjects)->first(fn($s) => mb_strpos($normalizedTitle, $s) !== false);

        // Find level in DB
        $level = $matchedLevel
            ? Level::where('name', 'like', '%' . $matchedLevel . '%')->first()
            : null;
        if (!$level) {
            return response()->json(['error' => 'No matching level found in the title.'], 400);
        }


            // Save Courses if required types found and no specific subject
            $requiredTypes = ['تمارين', 'ملخصات', 'دروس'];
            $hasAllTypes = !array_diff($requiredTypes, $matchedTypes);
            $islessonPage = $crawler->filter('div.admasafa')->first();


            if ($hasAllTypes && !$matchedSubject && !$islessonPage->count()) {
                foreach ($content as $line) {
                    $courseName = trim($line);
                    if (mb_strlen($courseName) > 3) {
                        Course::firstOrCreate([
                            'level_id' => $level->id,
                            'name' => $courseName,
                        ], [
                            'url' => $url
                        ]);
                    }
                }
            }
            $possibleSelectors = [
                '.entry-content #tableone',
                '.entry-content .dire #tableone',
                '.entry-content .table-responsive',
                '.entry-content .dire table-responsive',
                '.entry-content .dire table',
            ];
            $isDataPage = null;
            foreach ($possibleSelectors as $selector) {
                $candidate = $crawler->filter($selector);
                if ($candidate->count() > 0) {
                    $isDataPage = $candidate;
                    break;
                }
            }

            //dd($matchedSubject,$normalizedTitle);
           // Save Lessons if subject matched
            if ($matchedSubject && (!($isDataPage && $isDataPage->count() > 0))) {
            //dd('Matched subject:', $matchedSubject, 'Level:', $level->id);
            echo "is lesson page\n";
            if ($matchedSubject === 'التربية السلامية'){
                $matchedSubject = 'التربية الإسلامية';
            }
            if($normalizedTitle === 'دروس ملخصات تمارين الرياضيات جذع مشترك اداب و علوم انسانية'){
                $matchedSubject = 'الرياضيات – اداب';
            }
            if($normalizedTitle === 'دروس ملخصات تمارين علوم الحياة والارض جذع مشترك اداب وعلوم انسانية'){
                $matchedSubject = 'علوم الحياة والارض – اداب';
            }
            $course = Course::where('name', $matchedSubject)
                ->where('level_id', $level->id)
                ->first();

            //dd($course, $level->id,$matchedSubject);
                if ($course) {
                    foreach ($content as $line) {
                        $lessonTitle = trim($line);
                        if (mb_strlen($lessonTitle) > 3) {
                                Lesson::firstOrCreate([
                                'course_id' => $course->id,
                                'title' => $lessonTitle,
                            ], [
                                'url' => $url
                            ]);
                        }
                    }
                }
            }




if ($isDataPage && $isDataPage->count() > 0) {
    echo "Table found!\n";
    $matchedLevel = str_replace('المستوى الاول', 'الاول ابتدائي', $matchedLevel);
    $matchedLevel = str_replace('المستوى الثاني', 'الثاني ابتدائي', $matchedLevel);
    $matchedLevel = str_replace('المستوى الثالث', 'الثالث ابتدائي', $matchedLevel);
    $matchedLevel = str_replace( 'جذع مشترك', 'جذع مشترك', $matchedLevel);

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
    $types = ['دروس','درس', 'فروض', 'امتحانات', 'ملخصات','ملخص','وتمارين','و تمارين','2024-2025 مع التصحيح','ابتدائي ','دولي','تمارين','مسلك','علمي و اداب','علمي وتكنولوجي','اداب وعلوم انسانية','(علوم) وتكنولوجي','علمي','آداب وعلوم إنسانية','وتكنولوجية','و ','مع التصحيح','وطنية مادة','جهوية في ','محلية في','اقليمية في'];
    foreach($types as $type) {
        $matchLesson = str_replace($type, '', $matchLesson);
    }
    $matchLesson = trim($matchLesson);
      if($matchLesson === 'الفيزياء والكيمياء  علوم خيار فرنسية'){
        $matchLesson = 'الفيزياء والكيمياء  خيار فرنسية علوم الحياة والارض';
    }
    $lesson = Lesson::where('title', 'like', "%$matchLesson%")
        ->whereHas('course', function ($query) use ($level) {
            $query->where('level_id', $level->id);
        })
    ->first();    
    //dd($matchLesson, $matchedLevel);

  

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

    if (!$courseId) {
    $course = Course::where('level_id', $level->id)->first();
    if (!$course) {
        return response()->json(['error' => 'No matching course found for the level.'], 400);
    }

}
    $levelId = $level->id;

    
    echo "slug: $normalizedTitle\n";
    echo "matched level: $level\n";
    echo "lessonid: $lessonId, levelid: $levelId, courseid: $courseId\n";
    //dd($matchLesson, $matchedLevel, $levelId, $lessonId, $courseId);

    // Process each table
    $isDataPage->each(function ($table) use ($levelId, $lessonId, $courseId) {
        $headers = $table->filter('tr')->first()->filter('td, th')->each(function ($cell) {
            $text = trim($cell->text());
            return $text !== '' ? $text : 'عمود';
        });

        $table->filter('tr')->each(function ($row, $rowIndex) use ($headers, $levelId, $lessonId, $courseId) {
            if ($rowIndex === 0) return; // Skip headers

            $cells = $row->filter('td');

            $cells->each(function ($cell, $colIndex) use ($headers, $levelId, $lessonId, $courseId) {
                $link = $cell->filter('a');
                $text = trim($cell->text());

                if ($link->count() > 0 && isset($headers[$colIndex])) {
                    $linkHref = $link->attr('href');
                    $value = $headers[$colIndex];

                    $rowTitle = trim(optional($cell->ancestors()->filter('tr')->first()->filter('td')->eq(0))->text());
                    if (in_array($value, ['المرحلة الأولى', 'المرحلة الثانية', 'المرحلة الثالثة'])) {
                        $value = 'فروض';
                    }

                    Data::create([
                        'level_id'  => $levelId,
                        'lesson_id' => $lessonId,
                        'course_id' => $courseId,
                        'title'     => $rowTitle,
                        'url'       => $linkHref,
                        'value'     => $value,
                    ]);
                }
            });
        });
    });
}
           return view('welcome', compact('content'));

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to scrape the page.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}