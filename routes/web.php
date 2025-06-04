    <?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ScrapeEducationData;
use App\Http\Controllers\FrenshController;


// Page d'accueil avec formulaire pour scraper
Route::get('/', [ScrapeEducationData::class, 'form'])->name('home');
Route::post('/scrape', [ScrapeEducationData::class, 'scrape'])->name('scrape');
// Pages d'affichage des données
Route::get('/data', [DataController::class, 'index'])->name('data.index');
Route::get('/get-courses/{levelId}', [DataController::class, 'getCourses']);
Route::get('/get-lessons/{courseId}', [DataController::class, 'getLessons']);
Route::get('/get-data/{lessonId}/{levelId}/{courseId}', [DataController::class, 'getData']);
Route::get('/get-exam-data', [DataController::class, 'getExamData']);
Route::get('/get-special-data', [DataController::class, 'getSpecialData']);



