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
            $types = ['دروس', 'فروض', 'امتحانات', 'ملخصات', 'تمارين'];
            $subjects = [
                'الرياضيات', 'الفيزياء والكيمياء', 'علوم الحياة والارض', 'اللغة الانجليزية', 'اللغة الفرنسية',
                'اللغة العربية', 'الفلسفة', 'التاريخ والجغرافيا', 'علوم المهندس', 'القانون',
                'المحاسبة والرياضيات المالية', 'الاقتصاد والتنظيم الاداري للمقاولات', 'الإقتصاد العام والإحصاء',
                'معلوميات التدبير', 'الفقه والاصول', 'التربية الاسلامية'
            ];

            // Normalize title
            $normalizedTitle = preg_replace('/\s+/', ' ', $title);

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

            if ($hasAllTypes && !$matchedSubject) {
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

            // Save Lessons if subject matched
            if ($matchedSubject) {
            $course = Course::where('name', 'like', '%' . $matchedSubject . '%')
                ->where('level_id', $level->id)
                ->first();
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

            // save the links as data    #tableone  .table-responsive
            $possibleSelectors = [
            '.entry-content #tableone',
            '.entry-content .table-responsive',
            '.entry-content .dire table-responsive',
        ];  
        //$isDataPage = $crawler->filter('.entry-content #tableone');
        $isDataPage = null;
        foreach ($possibleSelectors as $selector) {
            $candidate = $crawler->filter($selector);
            if ($c`andidate->count() > 0) {
                $isDataPage = $candidate;
                break;
            }
        }
        if ($isDataPage->count() > 0) {
        echo "Data page detected";

        // Extract slug from URL
        $urlParts = parse_url($url);
        $path = $urlParts['path'] ?? '';
        $slug = trim(basename($path), '/');
        $decodedSlug = urldecode($slug);

        // Process slug
        $slugWords = explode('-', $decodedSlug);
        $joinedSlug = implode(' ', $slugWords);

        // Match level
        $matchedLevel = null;
        foreach ($levels as $level) {
            if (mb_strpos($joinedSlug, $level) !== false) {
                $matchedLevel = $level;
                break;
            }
        }

        // Remove level to get lesson name
        $lessonName = $matchedLevel ? str_replace($matchedLevel, '', $joinedSlug) : $joinedSlug;
        $lessonName = trim($lessonName);

        // Get levelId and lessonId
        $levelId = $matchedLevel
            ? optional(Level::where('name', 'like', '%' . $matchedLevel . '%')->first())->id
            : null;

        $lessonId = $lessonName
            ? optional(Lesson::where('title', 'like', '%' . $lessonName . '%')->first())->id
            : null;

        $courseId = $lessonId ? Lesson::where('id', $lessonId)->value('course_id') : null;

        // Extract table headers
        $headers = [];
        $crawler->filter('#tableone tr')->first()->filter('td, th')->each(function ($cell) use (&$headers) {
            $headerText = trim($cell->text());
            $headers[] = $headerText !== '' ? $headerText : 'عمود';
        });

        // Loop over each table row
        $crawler->filter('#tableone tr')->each(function ($row, $index) use ($headers, $levelId, $lessonId, $courseId) {
            if ($index === 0) return; // Skip header row

            $row->filter('td')->each(function ($cell, $cellIndex) use ($headers, $levelId, $lessonId, $courseId) {
                $link = $cell->filter('a');
                if ($link->count() > 0) {
                    $linkText = trim($link->text());
                    $linkHref = $link->attr('href');

                    Data::create([
                        'level_id' => $levelId,
                        'lesson_id' => $lessonId,
                        'course_id' => $courseId,
                        'exercise_id' => null,
                        'title' => $linkText !== '' ? $linkText : ($headers[$cellIndex] ?? 'رابط'),
                        'url' => $linkHref,
                        'value' => $headers[$cellIndex] ?? null, // this will be 'دروس', 'ملخصات', etc.
                    ]);
                }
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
