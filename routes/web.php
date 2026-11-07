<?php

use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Account\SuspensionController;
use App\Http\Controllers\Account\UserListingController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Admin\ModerationListingController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\LocationApiController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CategoryWebController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ListingWebController;
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
    Route::get('/{stateCode}/{citySlug}/{categorySlug}', [LocationWebController::class, 'showCity'])->name('city.category');
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
    Route::get('/history', [AdminLocationController::class, 'importHistory'])->name('history');
    Route::post('/reimport', [AdminLocationController::class, 'triggerImport'])->name('reimport');
    Route::put('/cities/{city}', [AdminLocationController::class, 'updateCity'])->name('updateCity');
    Route::post('/cities/{city}/aliases', [AdminLocationController::class, 'storeAlias'])->name('storeAlias');
    Route::delete('/aliases/{alias}', [AdminLocationController::class, 'destroyAlias'])->name('destroyAlias');
    Route::post('/cities/{city}/zips', [AdminLocationController::class, 'storeZip'])->name('storeZip');
});

/*
|--------------------------------------------------------------------------
| Dynamic Personal Category Engine Routes
|--------------------------------------------------------------------------
*/

// Public Category Directory & Landing Pages
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [CategoryWebController::class, 'index'])->name('index');
    Route::get('/{slug}', [CategoryWebController::class, 'show'])->name('show');
});

// Category API Endpoints
Route::prefix('api/categories')->name('api.categories.')->group(function () {
    Route::get('/tree', [CategoryApiController::class, 'tree'])->name('tree');
    Route::get('/{slug}', [CategoryApiController::class, 'show'])->name('show');
    Route::get('/{category}/attributes', [CategoryApiController::class, 'attributes'])->name('attributes');
});

// Administrative Category Management
Route::middleware('auth')->prefix('admin/categories')->name('admin.categories.')->group(function () {
    Route::get('/', [AdminCategoryController::class, 'index'])->name('index');
    Route::get('/create', [AdminCategoryController::class, 'create'])->name('create');
    Route::post('/', [AdminCategoryController::class, 'store'])->name('store');
    Route::get('/{category}/edit', [AdminCategoryController::class, 'edit'])->name('edit');
    Route::put('/{category}', [AdminCategoryController::class, 'update'])->name('update');
    Route::delete('/{category}', [AdminCategoryController::class, 'destroy'])->name('destroy');
    Route::get('/{category}/preview', [AdminCategoryController::class, 'preview'])->name('preview');
    Route::get('/{category}/reassign', [AdminCategoryController::class, 'reassign'])->name('reassign');
    Route::post('/{category}/reassign', [AdminCategoryController::class, 'doReassign'])->name('reassign.action');
    
    // Dynamic Attributes Management
    Route::get('/{category}/attributes', [AdminCategoryController::class, 'attributes'])->name('attributes');
    Route::post('/{category}/attributes', [AdminCategoryController::class, 'storeAttribute'])->name('attributes.store');
    Route::delete('/attributes/{attribute}', [AdminCategoryController::class, 'deleteAttribute'])->name('attributes.destroy');
    Route::post('/attributes/{attribute}/options', [AdminCategoryController::class, 'storeAttributeOption'])->name('attributes.options.store');
    Route::delete('/attributes/options/{option}', [AdminCategoryController::class, 'deleteAttributeOption'])->name('attributes.options.destroy');
});

/*
|--------------------------------------------------------------------------
| Listing Lifecycle & Moderation Routes
|--------------------------------------------------------------------------
*/

// Listing Wizard & Detail
Route::middleware('auth')->prefix('listings')->name('listings.')->group(function () {
    Route::get('/create', [ListingWebController::class, 'create'])->name('create');
    Route::get('/{listing}/edit', [ListingWebController::class, 'edit'])->name('edit');
});

Route::get('/listings/{slug}', [ListingWebController::class, 'show'])->name('listings.show');

// User Account Listing Dashboard
Route::middleware('auth')->prefix('account/listings')->name('account.listings.')->group(function () {
    Route::get('/', [UserListingController::class, 'index'])->name('index');
    Route::post('/{listing}/renew', [UserListingController::class, 'renew'])->name('renew');
    Route::post('/{listing}/pause', [UserListingController::class, 'pause'])->name('pause');
    Route::post('/{listing}/resume', [UserListingController::class, 'resume'])->name('resume');
    Route::delete('/{listing}', [UserListingController::class, 'destroy'])->name('destroy');
});

// Admin Moderation Queue
Route::middleware('auth')->prefix('admin/moderation/listings')->name('admin.moderation.listings.')->group(function () {
    Route::get('/', [ModerationListingController::class, 'queue'])->name('queue');
    Route::post('/{listing}/approve', [ModerationListingController::class, 'approve'])->name('approve');
    Route::post('/{listing}/reject', [ModerationListingController::class, 'reject'])->name('reject');
    Route::post('/{listing}/suspend', [ModerationListingController::class, 'suspend'])->name('suspend');
});
