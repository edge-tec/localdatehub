<?php

use App\Http\Controllers\HealthController;
use App\Modules\Categories\Services\CategoryService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web & API Foundation Routes
|--------------------------------------------------------------------------
*/

// Public Welcome & Discovery Landing Page
Route::get('/', function (CategoryService $categoryService) {
    return view('welcome', [
        'categories' => $categoryService->getActiveCategories(),
    ]);
})->name('home');

// Health Check Endpoints
Route::prefix('health')->group(function () {
    // Basic liveness probe (public for load balancers & uptime monitors)
    Route::get('/', [HealthController::class, 'liveness'])->name('health.liveness');

    // Detailed readiness & infrastructure diagnostic probe (protected by X-Health-Key)
    Route::get('/detailed', [HealthController::class, 'readiness'])
        ->middleware('health.auth')
        ->name('health.readiness');
});

// Age Assurance Gate
Route::get('/age-confirmation', function () {
    return view('welcome', [
        'categories' => app(CategoryService::class)->getActiveCategories(),
    ]);
})->name('age.gate');

Route::post('/age-confirmation/confirm', function (Request $request) {
    $request->session()->put('age_confirmed_18', true);
    return redirect()->intended(route('home'))->with('status', 'Age verified: Welcome to LocalDateHub.');
})->name('age.confirm');
