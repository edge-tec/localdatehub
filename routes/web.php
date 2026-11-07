<?php

use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Account\SuspensionController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Api\LocationApiController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LocationWebController;
use App\Modules\Categories\Services\CategoryService;
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
    return view('auth.age-gate');
})->name('age.gate');

Route::post('/age-confirmation/confirm', function (Request $request) {
    $request->session()->put('age_confirmed_18', true);
    return redirect()->intended(route('home'))->with('status', 'Age verified: Welcome to LocalDateHub.');
})->name('age.confirm');

/*
|--------------------------------------------------------------------------
| Guest Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Registration
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    // Login
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Two-Factor Authentication Challenge
    Route::get('/login/2fa', [AuthenticatedSessionController::class, 'show2fa'])->name('login.2fa');
    Route::post('/login/2fa', [AuthenticatedSessionController::class, 'verify2fa'])->name('login.2fa.verify');

    // Password Reset
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Session Termination
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Email Verification Notice & Handlers
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Suspended Account State & Appeals (Exempt from active check)
    Route::get('/account/suspended', [SuspensionController::class, 'show'])->name('account.suspended');
    Route::post('/account/appeal', [SuspensionController::class, 'appeal'])
        ->middleware('throttle:5,1')
        ->name('account.appeal');

    // Protected Account Settings (Must be active)
    Route::middleware('active')->prefix('account')->name('account.')->group(function () {
        // Profile Management
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');

        // Security & 2FA
        Route::get('/security', [SecurityController::class, 'edit'])->name('security');
        Route::put('/security/password', [SecurityController::class, 'updatePassword'])->name('security.password');
        Route::post('/security/2fa/generate', [SecurityController::class, 'generate2fa'])->name('security.2fa.generate');
        Route::post('/security/2fa/confirm', [SecurityController::class, 'confirm2fa'])->name('security.2fa.confirm');
        Route::post('/security/2fa/disable', [SecurityController::class, 'disable2fa'])->name('security.2fa.disable');

        // Self-Service Account Deletion (GDPR / CCPA)
        Route::delete('/', [SecurityController::class, 'destroy'])->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Geographic Location Engine Routes
|--------------------------------------------------------------------------
*/

// Public Web Location Directory & Landing Pages
Route::prefix('locations')->name('locations.')->group(function () {
    Route::get('/', [LocationWebController::class, 'index'])->name('index');
    Route::get('/{stateCode}', [LocationWebController::class, 'showState'])->name('state');
    Route::get('/{stateCode}/{citySlug}', [LocationWebController::class, 'showCity'])->name('city');
});

// Location API Endpoints
Route::prefix('api/locations')->name('api.locations.')->group(function () {
    Route::get('/states', [LocationApiController::class, 'indexStates'])->name('states');
    Route::get('/states/{stateCode}/cities', [LocationApiController::class, 'stateCities'])->name('state.cities');
    Route::get('/cities/{cityId}', [LocationApiController::class, 'cityDetails'])->name('city.details');
    Route::get('/zip/{zip}', [LocationApiController::class, 'lookupZip'])->name('zip');
    Route::get('/autocomplete', [LocationApiController::class, 'autocomplete'])->name('autocomplete');
    Route::get('/nearby', [LocationApiController::class, 'nearby'])->name('nearby');
});

// Administrative Location Management
Route::middleware('auth')->prefix('admin/locations')->name('admin.locations.')->group(function () {
    Route::get('/', [AdminLocationController::class, 'index'])->name('index');
    Route::put('/cities/{city}', [AdminLocationController::class, 'updateCity'])->name('updateCity');
    Route::post('/cities/{city}/aliases', [AdminLocationController::class, 'storeAlias'])->name('storeAlias');
    Route::delete('/aliases/{alias}', [AdminLocationController::class, 'destroyAlias'])->name('destroyAlias');
    Route::post('/cities/{city}/zips', [AdminLocationController::class, 'storeZip'])->name('storeZip');
});
