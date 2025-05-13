<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScraperController;

Route::get('/', [ScraperController::class, 'form']);
Route::post('/scrape', [ScraperController::class, 'scrape']);
