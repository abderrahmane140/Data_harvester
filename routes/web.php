<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScrapeEducationData;
use App\Http\Controllers\DataDisplayController;

// Page d'accueil avec formulaire pour scraper
Route::get('/', [ScrapeEducationData::class, 'form']);
Route::post('/scrape', [ScrapeEducationData::class, 'scrape'])->name('scrape');

// Pages d'affichage des données
Route::get('/data', [DataDisplayController::class, 'index'])->name('data.index');

// Récupération des cours selon le niveau (pour le select dynamique)
Route::get('/courses/{level_id}', [DataDisplayController::class, 'getCourses']);

// Récupération des détails (leçons + exercices) d’un cours
Route::get('/details/{course_id}', [DataDisplayController::class, 'getDetails']);

// ----
Route::get('/details/{courseId}', [DetailsController::class, 'show']);
