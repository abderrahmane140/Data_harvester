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

class ScrapeEducationData extends Controller
{
    public function form()
    {
        return view('welcome');
    }

    public function scrape(Request $request)
    {
        // Validate input
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

            // Get title
            $titleNode = $crawler->filter('h1.entry-title')->first();
            $title = $titleNode->count() ? trim($titleNode->text()) : '';

            // Extract content
            $crawler->filter('div.inside-article')->filter('p, .mada a')->each(function ($node) use (&$content) {
                $text = trim($node->text());
                if (!empty($text)) {
                    $content[] = $text;
                }
            });

            // Define reference data
            $levels = ['الثانية باك', 'اولى باك', 'جذع مشترك','الثالثة اعدادي','الثانية اعدادي','الاولى اعدادي','السادس ابتدائي','الخامس ابتدائي','الرابع ابتدائي','الثالث ابتدائي','الثاني ابتدائي','الاول ابتدائي'];
            $types = ['دروس', 'فروض', 'امتحانات', 'ملخصات', 'تمارين'];
            $subjects = [
                'الرياضيات', 'الفيزياء والكيمياء', 'علوم الحياة والارض', 'اللغة الانجليزية', 'اللغة الفرنسية', 
                'اللغة العربية', 'الفلسفة', 'التاريخ والجغرافيا', 'علوم المهندس', 'القانون', 
                'المحاسبة والرياضيات المالية', 'الاقتصاد والتنظيم الاداري للمقاولات', 'الإقتصاد العام والإحصاء', 
                'معلوميات التدبير', 'الفقه والاصول', 'التربية الاسلامية'
            ];

            // Normalize title
            $normalizedTitle = trim(preg_replace('/\s+/', ' ', $title));

            // Match level and types
            $matchedLevels = array_filter($levels, fn($level) => mb_strpos($normalizedTitle, $level) !== false);
            $matchedTypes = array_filter($types, fn($type) => mb_strpos($normalizedTitle, $type) !== false);
            $matchedSubject = collect($subjects)->first(fn($subject) => mb_strpos($normalizedTitle, $subject) !== false);

            $level = !empty($matchedLevels) ? Level::where('name', 'like', '%' . reset($matchedLevels) . '%')->first() : null;

            if (!$level) {
                return response()->json(['error' => 'No matching level found in the title.'], 400);
            }

            // ✅ Save Courses if page contains lessons/summary/exercises
            $requiredTypes = ['تمارين', 'ملخصات', 'دروس'];
            if (count(array_intersect($requiredTypes, $matchedTypes)) === count($requiredTypes)) {
                foreach ($content as $line) {
                    $courseName = trim($line);
                    if (mb_strlen($courseName) > 3) {
                        Course::firstOrCreate([
                            'level_id' => $level->id,
                            'name' => $courseName,
                            'url' => $url
                        ]);
                    }
                }
            }

            // ✅ Save Lessons if subject matched
            if ($matchedSubject) {
                $course = Course::where('name', 'like', '%' . $matchedSubject . '%')->first();
                if ($course) {
                    foreach ($content as $line) {
                        $lessonName = trim($line);
                        if (mb_strlen($lessonName) > 3) {
                            Lesson::firstOrCreate([
                                'course_id' => $course->id,
                                'title' => $lessonName,
                                'url' => $url
                            ]);
                        }
                    }
                }
            }

            // Response
            return view('welcome', compact('content'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to scrape the page.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
