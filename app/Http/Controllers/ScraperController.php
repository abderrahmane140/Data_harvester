<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\DomCrawler\Crawler;

class ScraperController extends Controller
{
    public function form()
    {
        return view('welcome');
    }

public function scrape(Request $request)
    {
        // ✅ Validate URL input
        $validator = Validator::make($request->all(), [
            'url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid or missing URL.'], 400);
        }

        $url = $request->input('url');

        try {
            // ✅ Make simple HTTP request
            $response = Http::get($url);

            if (!$response->ok()) {
                return response()->json([
                    'error' => 'Failed to retrieve the page.',
                    'status' => $response->status()
                ], 500);
            }

            $html = $response->body();

            // ✅ Parse HTML using DomCrawler
            $crawler = new Crawler($html);

            $content = [];
            $crawler->filter('h1, h2, h3, p, li, a')->each(function ($node) use (&$content) {
                $text = trim($node->text());
                if (!empty($text)) {
                    $content[] = $text;
                }
            });

            // ✅ Save to DB
            DB::table('scraped_data')->insert([
                'url' => $url,
                'data' => json_encode($content, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // return response()->json([
            //     'message' => 'Scraping completed successfully.',
            //     'url' => $url,
            //     'content' => $content,
            // ]);
            
            return view('welcome', compact('content'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to scrape the page.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
