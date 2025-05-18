<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScrapeEducationData;

Route::get('/', [ScrapeEducationData::class, 'form']);
Route::post('/scrape', [ScrapeEducationData::class, 'scrape'])->name('scrape');