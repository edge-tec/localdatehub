<?php

use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Account\SuspensionController;
use App\Http\Controllers\Account\UserListingController;
use App\Http\Controllers\Account\UserMessagesController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Admin\ModerationListingController;
use App\Http\Controllers\Admin\ModerationMessagesController;
use App\Http\Controllers\ListingContactWebController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\ListingApiController;
use App\Http\Controllers\Api\LocationApiController;
use App\Http\Controllers\Api\SearchApiController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CategoryWebController;
use App\Http\Controllers\Billing\InvoiceWebController;
use App\Http\Controllers\Billing\PaymentWebhookController;
use App\Http\Controllers\DmcaWebController;
use App\Http\Controllers\SEO\SitemapController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingWebController;
use App\Http\Controllers\LocationWebController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SearchWebController;
use App\Http\Controllers\UserReportWebController;
use App\Modules\Categories\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web & API Foundation Routes
|--------------------------------------------------------------------------
*/

// Public Welcome & Discovery Landing Page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Search & Filtering Classifieds Stream
Route::get('/search', [SearchWebController::class, 'index'])->name('search.index');

// Public Information & Trust Pages
Route::get('/about', [PublicPageController::class, 'about'])->name('about');
Route::get('/about-us', [PublicPageController::class, 'about'])->name('pages.about');
Route::get('/safety', [PublicPageController::class, 'safety'])->name('pages.safety');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('contact');
Route::get('/contact-us', [PublicPageController::class, 'contact'])->name('pages.contact');
Route::post('/contact', [PublicPageController::class, 'submitContact'])
    ->middleware('throttle:10,1')
    ->name('pages.contact.submit');
Route::get('/privacy', [PublicPageController::class, 'privacy'])->name('privacy');
Route::get('/privacy-policy', [PublicPageController::class, 'privacy'])->name('pages.privacy');
Route::get('/terms', [PublicPageController::class, 'terms'])->name('terms');
Route::get('/terms-of-service', [PublicPageController::class, 'terms'])->name('pages.terms');
Route::get('/pricing', [PublicPageController::class, 'pricing'])->name('pages.pricing');

// DMCA Notice & Takedown Portal
Route::get('/dmca', [DmcaWebController::class, 'show'])->name('dmca.show');
Route::post('/dmca', [DmcaWebController::class, 'submit'])
    ->middleware('throttle:10,1')
    ->name('dmca.submit');

// User Account Reporting
Route::post('/users/{user}/report', [UserReportWebController::class, 'submit'])
    ->middleware(['throttle:10,1'])
    ->name('users.report.submit');

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
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');

    // Login
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');

    // Social Authentication (Google, Facebook)
    Route::get('/auth/{provider}', [\App\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])
        ->middleware('throttle:15,1')
        ->name('social.redirect');
    Route::get('/auth/{provider}/callback', [\App\Http\Controllers\Auth\SocialAuthController::class, 'callback'])
        ->middleware('throttle:15,1')
        ->name('social.callback');

    // Two-Factor Authentication Challenge
    Route::get('/login/2fa', [AuthenticatedSessionController::class, 'show2fa'])->name('login.2fa');
    Route::post('/login/2fa', [AuthenticatedSessionController::class, 'verify2fa'])
        ->middleware('throttle:6,1')
        ->name('login.2fa.verify');

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
        // Central Unified Account Dashboard (Livewire-Powered)
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/', [DashboardController::class, 'index'])->name('index');

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

        // Private Message Inbox & Inquiry Management
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [UserMessagesController::class, 'index'])->name('index');
            Route::get('/{message}', [UserMessagesController::class, 'show'])->name('show');
            Route::post('/{message}/replied', [UserMessagesController::class, 'markReplied'])->name('replied');
            Route::post('/{message}/flag', [UserMessagesController::class, 'flag'])->name('flag');
            Route::delete('/{message}', [UserMessagesController::class, 'destroy'])->name('destroy');
        });
    });

    // Real-Time Notification & Message Read/Seen Synchronization
    Route::post('/notifications/mark-seen', function (Request $request) {
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false], 401);
        }

        $now = now();
        $scope = (string) $request->input('scope', 'user');

        if ($scope === 'admin' && (method_exists($user, 'isStaff') && $user->isStaff() || ($user->is_admin ?? false))) {
            \App\Modules\Notifications\Models\NotificationLog::where('is_read', false)->update([
                'is_read' => true,
                'read_at' => $now,
            ]);
            \App\Modules\Moderation\Models\ContentReport::where('is_read', false)->update([
                'is_read' => true,
                'read_at' => $now,
            ]);
            \App\Modules\Contact\Models\ContactMessage::where(function ($q) use ($user) {
                $q->where('is_flagged', true)->orWhere('recipient_id', $user->id)->orWhereNull('listing_id');
            })->where('is_read', false)->update([
                'is_read' => true,
                'read_at' => $now,
            ]);
        } else {
            \App\Modules\Notifications\Models\NotificationLog::where('user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => $now,
                ]);

            if ($request->boolean('include_messages')) {
                \App\Modules\Contact\Models\ContactMessage::where('recipient_id', $user->id)
                    ->where('is_read', false)
                    ->update([
                        'is_read' => true,
                        'read_at' => $now,
                    ]);
            }
        }

        return response()->json(['ok' => true, 'read_at' => $now->toIso8601String()]);
    })->name('notifications.mark-seen');
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

// USA SEO-Friendly Location URLs (/usa/texas/houston)
Route::prefix('usa')->name('usa.')->group(function () {
    Route::get('/', [LocationWebController::class, 'index'])->name('index');
    Route::get('/{stateCode}', [LocationWebController::class, 'showState'])->name('state');
    Route::get('/{stateCode}/{citySlug}', [LocationWebController::class, 'showCity'])->name('city');
    Route::get('/{stateCode}/{citySlug}/{categorySlug}', [LocationWebController::class, 'showCity'])->name('city.category');
});

// Location API Endpoints
Route::prefix('api/locations')->name('api.locations.')->group(function () {
    Route::get('/states', [LocationApiController::class, 'indexStates'])->name('states');
    Route::get('/states/{stateCode}/cities', [LocationApiController::class, 'stateCities'])->name('state.cities');
    Route::get('/cities/search', [LocationApiController::class, 'searchCities'])->name('cities.search');
    Route::get('/cities/{cityId}', [LocationApiController::class, 'cityDetails'])->name('city.details');
    Route::get('/zip/{zip}', [LocationApiController::class, 'lookupZip'])->name('zip');
    Route::get('/autocomplete', [LocationApiController::class, 'autocomplete'])->name('autocomplete');
    Route::get('/nearby', [LocationApiController::class, 'nearby'])->name('nearby');
});

// Listing API Endpoints
Route::prefix('api/listings')->name('api.listings.')->group(function () {
    Route::get('/location/{state}/{city}', [ListingApiController::class, 'locationListings'])->name('location');
    Route::get('/search', [ListingApiController::class, 'search'])->name('search');
    Route::post('/', [ListingApiController::class, 'store'])->middleware('auth')->name('store');
});

// Search API Endpoints
Route::prefix('api/search')->name('api.search.')->group(function () {
    Route::get('/autocomplete', [SearchApiController::class, 'autocomplete'])->name('autocomplete');
});

// Central Enterprise Administration Hub
Route::middleware(['auth'])->get('/admin/impersonate/stop', [AdminDashboardController::class, 'stopImpersonation'])->name('admin.impersonate.stop');

Route::middleware(['auth', 'admin.rbac'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('index');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications/feed', [AdminDashboardController::class, 'notificationFeed'])->name('notifications.feed');
});

// Administrative Location Management
Route::middleware(['auth', 'admin.rbac:locations.manage'])->prefix('admin/locations')->name('admin.locations.')->group(function () {
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
Route::middleware(['auth', 'admin.rbac:categories.manage'])->prefix('admin/categories')->name('admin.categories.')->group(function () {
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

// Public Listing Inquiries, Abuse Reporting, and Favorites
Route::post('/listings/{listing}/contact', [ListingContactWebController::class, 'submitInquiry'])
    ->middleware('throttle:20,5')
    ->name('listings.contact.submit');
Route::get('/listings/{listing}/report', function (\App\Modules\Listings\Models\Listing $listing) {
    return redirect()->route('listings.show', $listing->slug);
});
Route::post('/listings/{listing}/report', [ListingContactWebController::class, 'reportListing'])
    ->middleware('throttle:20,5')
    ->name('listings.report.submit');
Route::post('/listings/{listing}/favorite', [ListingContactWebController::class, 'toggleFavorite'])
    ->name('listings.favorite.toggle');

// User Account Listing Dashboard
Route::middleware('auth')->prefix('account/listings')->name('account.listings.')->group(function () {
    Route::get('/', [UserListingController::class, 'index'])->name('index');
    Route::post('/{listing}/renew', [UserListingController::class, 'renew'])->name('renew');
    Route::post('/{listing}/pause', [UserListingController::class, 'pause'])->name('pause');
    Route::post('/{listing}/resume', [UserListingController::class, 'resume'])->name('resume');
    Route::delete('/{listing}', [UserListingController::class, 'destroy'])->name('destroy');
});

// Admin Moderation Queue
Route::middleware(['auth', 'admin.rbac:moderation.view'])->prefix('admin/moderation/listings')->name('admin.moderation.listings.')->group(function () {
    Route::get('/', [ModerationListingController::class, 'queue'])->name('queue');
    Route::post('/{listing}/approve', [ModerationListingController::class, 'approve'])->name('approve');
    Route::post('/{listing}/reject', [ModerationListingController::class, 'reject'])->name('reject');
    Route::post('/{listing}/suspend', [ModerationListingController::class, 'suspend'])->name('suspend');
});

// Admin Moderation Messages & Safety Reports Queue
Route::middleware(['auth', 'admin.rbac:moderation.view'])->prefix('admin/moderation/messages')->name('admin.moderation.messages.')->group(function () {
    Route::get('/', [ModerationMessagesController::class, 'index'])->name('queue');
    Route::post('/{message}/dismiss', [ModerationMessagesController::class, 'dismiss'])->name('dismiss');
    Route::post('/{message}/action', [ModerationMessagesController::class, 'takeAction'])->name('action');
});

// Monetization, Invoices & Webhooks
Route::middleware('auth')->get('/account/invoices/{invoice}', [InvoiceWebController::class, 'show'])->name('account.invoices.show');
Route::post('/webhooks/payments/{gateway}', [PaymentWebhookController::class, 'handle'])->name('webhooks.payments');
Route::post('/api/payments/webhook/{gateway}', [PaymentWebhookController::class, 'handle'])->name('api.webhooks.payments');

// SEO, Sitemaps & Robots.txt (Stateless, Public Caching, No Session Cookies)
Route::withoutMiddleware('web')->group(function () {
    Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('seo.robots');
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get('/sitemap_index.xml', [SitemapController::class, 'index'])->name('sitemap.index.alias');
    Route::prefix('sitemaps')->name('sitemaps.')->group(function () {
        Route::get('/pages.xml', [SitemapController::class, 'pages'])->name('pages');
        Route::get('/categories.xml', [SitemapController::class, 'categories'])->name('categories');
        Route::get('/locations.xml', [SitemapController::class, 'locations'])->name('locations');
        Route::get('/states.xml', [SitemapController::class, 'states'])->name('states');
        Route::get('/cities.xml', [SitemapController::class, 'cities'])->name('cities');
        Route::get('/listings.xml', [SitemapController::class, 'listings'])->name('listings');
        Route::get('/images.xml', [SitemapController::class, 'images'])->name('images');
    });

    // Dynamic Google Search Console HTML verification file support (e.g. /google1234567890abcdef.html)
    Route::get('/google{code}.html', [SitemapController::class, 'googleVerification'])
        ->where('code', '[a-zA-Z0-9_\-]+')
        ->name('seo.google.verify');

    // Explicit SEO URI prefix aliases (supports `php artisan route:list --path=seo`)
    Route::prefix('seo')->name('seo.')->group(function () {
        Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
        Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots.custom');
        Route::get('/health', [SitemapController::class, 'health'])->name('health');
        Route::get('/google/status', [SitemapController::class, 'googleStatus'])->name('google.status');
        Route::get('/google/inspection', [SitemapController::class, 'googleInspection'])->name('google.inspection');
        Route::get('/google/webmaster', [SitemapController::class, 'googleStatus'])->name('google.webmaster');
    });
});

// Google Indexing & SEO Webhooks (CSRF-exempt)
Route::post('/webhooks/seo/google/indexing', [SitemapController::class, 'googleIndexingWebhook'])->name('webhooks.seo.google.indexing');

// =========================================================================
// Enterprise Advertising & Affiliate Campaign Delivery & Tracking Routes
// =========================================================================
Route::get('/api/ads/serve', [\App\Http\Controllers\Advertising\AdDeliveryApiController::class, 'serve'])
    ->middleware('throttle:240,1')
    ->name('ads.api.serve');
Route::get('/ads/serve', [\App\Http\Controllers\Advertising\AdDeliveryApiController::class, 'serve'])
    ->middleware('throttle:240,1')
    ->name('ads.serve');
Route::get('/ads/click/{campaign:uuid}', [\App\Http\Controllers\Advertising\AdTrackingController::class, 'click'])
    ->middleware('throttle:60,1')
    ->name('ads.click');
Route::get('/ads/legacy-click/{placement}', [\App\Http\Controllers\Advertising\AdTrackingController::class, 'legacyClick'])
    ->middleware('throttle:60,1')
    ->name('ads.legacy.click');
Route::post('/ads/impression', [\App\Http\Controllers\Advertising\AdTrackingController::class, 'impression'])
    ->middleware('throttle:120,1')
    ->name('ads.impression');
Route::post('/api/ads/impression', [\App\Http\Controllers\Advertising\AdTrackingController::class, 'impression'])
    ->middleware('throttle:120,1')
    ->name('ads.api.impression');
Route::match(['get', 'post'], '/webhooks/ads/postback/{campaign:uuid}', [\App\Http\Controllers\Advertising\AdTrackingController::class, 'postback'])
    ->middleware('throttle:120,1')
    ->name('ads.postback');

// Admin SEO & Google Search Console Management Routes
Route::middleware(['auth', 'admin.rbac'])->prefix('admin/seo')->name('admin.seo.')->group(function () {
    Route::match(['get', 'post'], '/google/sync', [SitemapController::class, 'googleSync'])->name('google.sync');
    Route::match(['get', 'post'], '/google/inspect', [SitemapController::class, 'googleInspection'])->name('google.inspect');
    Route::get('/google/console', [SitemapController::class, 'googleStatus'])->name('google.console');
    Route::get('/webmaster/status', [SitemapController::class, 'googleStatus'])->name('webmaster.status');
});

// =========================================================================
// Enterprise Cryptocurrency Payment Gateway & Automatic Post Boost System
// =========================================================================

// 1. Webhooks (CSRF-exempt via bootstrap/app.php)
Route::prefix('webhooks/crypto')->name('webhooks.crypto.')->group(function () {
    Route::post('/nowpayments', [\App\Http\Controllers\Payments\CryptoWebhookController::class, 'handleNowPayments'])->name('nowpayments');
    Route::post('/coinpayments', [\App\Http\Controllers\Payments\CryptoWebhookController::class, 'handleCoinPayments'])->name('coinpayments');
    Route::post('/coingate', [\App\Http\Controllers\Payments\CryptoWebhookController::class, 'handleCoinGate'])->name('coingate');
    Route::post('/bitpay', [\App\Http\Controllers\Payments\CryptoWebhookController::class, 'handleBitPay'])->name('bitpay');
    Route::post('/binance-pay', [\App\Http\Controllers\Payments\CryptoWebhookController::class, 'handleBinancePay'])->name('binance-pay');
    Route::post('/exodus', [\App\Http\Controllers\Payments\CryptoWebhookController::class, 'handleExodus'])->name('exodus');
});

// 2. User Post Boost Checkout, Invoices & History
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/listings/{listing}/boost', [\App\Http\Controllers\Payments\BoostCheckoutController::class, 'show'])->name('boost.checkout');
    Route::post('/listings/{listing}/boost/checkout', [\App\Http\Controllers\Payments\BoostCheckoutController::class, 'createPayment'])->name('boost.create');
    Route::post('/listings/{listing}/boost/wallet-credits', [\App\Http\Controllers\Payments\BoostCheckoutController::class, 'payWithCredits'])->name('boost.pay-with-credits');
    Route::get('/payments/crypto/{orderId}', [\App\Http\Controllers\Payments\BoostCheckoutController::class, 'showInvoice'])->name('boost.invoice');
    Route::get('/payments/crypto/{orderId}/status', [\App\Http\Controllers\Payments\BoostCheckoutController::class, 'checkStatus'])->name('boost.status');
    Route::get('/account/payments', [\App\Http\Controllers\Payments\BoostCheckoutController::class, 'userHistory'])->name('account.payments.index');

    // Credit Wallet & Packages
    Route::get('/account/wallet', [\App\Http\Controllers\Account\UserWalletController::class, 'index'])->name('account.wallet.index');
    Route::get('/account/crypto-wallet', [\App\Http\Controllers\Account\CryptoWalletDashboardController::class, 'index'])->name('account.crypto-wallet.index');
    Route::get('/account/wallet/topup-amount', [\App\Http\Controllers\Account\UserWalletController::class, 'customTopup'])->name('account.wallet.topup-amount');
    Route::get('/account/wallet/checkout/{package}', [\App\Http\Controllers\Account\UserWalletController::class, 'checkout'])->name('account.wallet.checkout');
    Route::post('/account/wallet/checkout/{package}', [\App\Http\Controllers\Account\UserWalletController::class, 'storePayment'])->name('account.wallet.payment');
    Route::get('/account/wallet/invoice/{payment}', [\App\Http\Controllers\Account\UserWalletController::class, 'invoice'])->name('account.wallet.invoice');
    Route::get('/account/wallet/invoice/{payment}/status', [\App\Http\Controllers\Account\UserWalletController::class, 'status'])->name('account.wallet.status');

    // Identity Verification
    Route::get('/account/verification', [\App\Http\Controllers\Account\AccountVerificationController::class, 'show'])->name('account.verification.show');
    Route::get('/account/verification/status', [\App\Http\Controllers\Account\AccountVerificationController::class, 'status'])->name('account.verification.status');
    Route::get('/api/account/verification', [\App\Http\Controllers\Account\AccountVerificationController::class, 'status'])->name('api.account.verification.status');
    Route::post('/account/verification', [\App\Http\Controllers\Account\AccountVerificationController::class, 'store'])->name('account.verification.store');
});

// 3. Admin Payment Gateway, Boost Plan & Transaction Management
Route::middleware(['auth', 'admin.rbac'])->prefix('admin/crypto')->name('admin.crypto.')->group(function () {
    // HD Wallet & Treasury Control Panel
    Route::get('/treasury', [\App\Http\Controllers\Admin\AdminHdTreasuryController::class, 'index'])->name('treasury');
    Route::post('/treasury/network/{id}/toggle', [\App\Http\Controllers\Admin\AdminHdTreasuryController::class, 'toggleNetwork'])->name('treasury.network.toggle');
    Route::post('/treasury/network/{id}/threshold', [\App\Http\Controllers\Admin\AdminHdTreasuryController::class, 'updateNetworkThreshold'])->name('treasury.network.threshold');
    Route::post('/treasury/asset/{id}/toggle', [\App\Http\Controllers\Admin\AdminHdTreasuryController::class, 'toggleAsset'])->name('treasury.asset.toggle');
    Route::post('/treasury/sweep/{id}/retry', [\App\Http\Controllers\Admin\AdminHdTreasuryController::class, 'retrySweep'])->name('treasury.sweep.retry');
    Route::post('/treasury/pause', [\App\Http\Controllers\Admin\AdminHdTreasuryController::class, 'pauseOutbound'])->name('treasury.pause');
    Route::post('/treasury/resume', [\App\Http\Controllers\Admin\AdminHdTreasuryController::class, 'resumeOutbound'])->name('treasury.resume');

    // Gateways
    Route::get('/gateways', [\App\Http\Controllers\Admin\AdminCryptoPaymentController::class, 'indexGateways'])->name('gateways.index');
    Route::get('/gateways/{gateway}/edit', [\App\Http\Controllers\Admin\AdminCryptoPaymentController::class, 'editGateway'])->name('gateways.edit');
    Route::put('/gateways/{gateway}', [\App\Http\Controllers\Admin\AdminCryptoPaymentController::class, 'updateGateway'])->name('gateways.update');
    Route::post('/gateways/{gateway}/test', [\App\Http\Controllers\Admin\AdminCryptoPaymentController::class, 'testGateway'])->name('gateways.test');

    // Boost Plans
    Route::get('/plans', [\App\Http\Controllers\Admin\AdminCryptoPaymentController::class, 'indexPlans'])->name('plans.index');
    Route::post('/plans', [\App\Http\Controllers\Admin\AdminCryptoPaymentController::class, 'storePlan'])->name('plans.store');
    Route::put('/plans/{plan}', [\App\Http\Controllers\Admin\AdminCryptoPaymentController::class, 'updatePlan'])->name('plans.update');
    Route::delete('/plans/{plan}', [\App\Http\Controllers\Admin\AdminCryptoPaymentController::class, 'destroyPlan'])->name('plans.destroy');

    // Payments
    Route::get('/payments', [\App\Http\Controllers\Admin\AdminCryptoPaymentController::class, 'indexPayments'])->name('payments.index');
    Route::get('/payments/{payment}', [\App\Http\Controllers\Admin\AdminCryptoPaymentController::class, 'showPayment'])->name('payments.show');
    Route::post('/payments/{payment}/sync', [\App\Http\Controllers\Admin\AdminCryptoPaymentController::class, 'syncPayment'])->name('payments.sync');
});

// Public Visitor Telemetry Heartbeat (throttled, non-invasive)
Route::post('/telemetry/heartbeat', [\App\Http\Controllers\VisitorTelemetryController::class, 'heartbeat'])
    ->middleware('throttle:60,1')
    ->name('telemetry.heartbeat');

// 4. Admin Posting Monetization, Wallet & Verification Management
Route::middleware(['auth', 'admin.rbac'])->group(function () {
    Route::get('/admin/live-visitors', \App\Livewire\Admin\LiveVisitorMonitor::class)->name('admin.live-visitors');
    Route::get('/admin/treasury-v2', \App\Livewire\Admin\TreasuryDashboard::class)->name('admin.treasury.v2');
    // Posting Policies & Monetization Control
    Route::prefix('admin/monetization')->name('admin.monetization.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminPostingMonetizationController::class, 'index'])->name('index');
        Route::put('/settings', [\App\Http\Controllers\Admin\AdminPostingMonetizationController::class, 'updateSettings'])->name('settings.update');
        Route::put('/limits/{accountType}', [\App\Http\Controllers\Admin\AdminPostingMonetizationController::class, 'updateAccountLimits'])->name('limits.update');
        Route::post('/pricing', [\App\Http\Controllers\Admin\AdminPostingMonetizationController::class, 'storePricingRule'])->name('pricing.store');
        Route::delete('/pricing/{rule}', [\App\Http\Controllers\Admin\AdminPostingMonetizationController::class, 'deletePricingRule'])->name('pricing.delete');
        Route::post('/overrides', [\App\Http\Controllers\Admin\AdminPostingMonetizationController::class, 'storeUserOverride'])->name('overrides.store');
        Route::delete('/overrides/{override}', [\App\Http\Controllers\Admin\AdminPostingMonetizationController::class, 'deleteUserOverride'])->name('overrides.delete');
    });

    // Wallet & Packages Management
    Route::prefix('admin/wallet')->name('admin.wallet.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminWalletController::class, 'index'])->name('index');
        Route::post('/packages', [\App\Http\Controllers\Admin\AdminWalletController::class, 'storePackage'])->name('packages.store');
        Route::put('/packages/{package}', [\App\Http\Controllers\Admin\AdminWalletController::class, 'updatePackage'])->name('packages.update');
        Route::post('/packages/{package}/toggle', [\App\Http\Controllers\Admin\AdminWalletController::class, 'togglePackage'])->name('packages.toggle');
        Route::delete('/packages/{package}', [\App\Http\Controllers\Admin\AdminWalletController::class, 'deletePackage'])->name('packages.delete');
        Route::post('/adjustment', [\App\Http\Controllers\Admin\AdminWalletController::class, 'manualAdjustment'])->name('adjustment');
    });

    // Identity Verification Queue (strictly requires verification.manage permission)
    Route::middleware('admin.rbac:verification.manage')->prefix('admin/verifications')->name('admin.verifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminVerificationController::class, 'index'])->name('index');
        Route::get('/{verification}', [\App\Http\Controllers\Admin\AdminVerificationController::class, 'show'])->name('show');
        Route::get('/{verification}/document/{type}', [\App\Http\Controllers\Admin\AdminVerificationController::class, 'downloadDocument'])->name('document');
        Route::post('/{verification}/under-review', [\App\Http\Controllers\Admin\AdminVerificationController::class, 'markUnderReview'])->name('under-review');
        Route::post('/{verification}/approve', [\App\Http\Controllers\Admin\AdminVerificationController::class, 'approve'])->name('approve');
        Route::post('/{verification}/reject', [\App\Http\Controllers\Admin\AdminVerificationController::class, 'reject'])->name('reject');
        Route::post('/{verification}/resubmit', [\App\Http\Controllers\Admin\AdminVerificationController::class, 'requestResubmission'])->name('resubmit');
        Route::post('/{verification}/revoke', [\App\Http\Controllers\Admin\AdminVerificationController::class, 'revoke'])->name('revoke');
        Route::post('/{verification}/suspend', [\App\Http\Controllers\Admin\AdminVerificationController::class, 'suspend'])->name('suspend');
    });
});

// Encrypted Email Relay System (In-App Composer + Inbound/Status Webhooks)
Route::post('/listings/{listing}/email-relay', [\App\Http\Controllers\EmailRelayController::class, 'send'])
    ->middleware('throttle:15,1')
    ->name('listings.email-relay.send');

Route::post('/webhooks/email-relay/inbound/{provider?}', [\App\Http\Controllers\InboundEmailController::class, 'receive'])
    ->name('webhooks.email-relay.inbound');

Route::post('/webhooks/email-relay/status/{provider?}', [\App\Http\Controllers\EmailRelayWebhookController::class, 'handle'])
    ->name('webhooks.email-relay.status');

// =========================================================================
// Five-Country Classified Marketplace System (GB, CA, AU, IE, NZ)
// =========================================================================
Route::get('/choose-country', [\App\Http\Controllers\CountryMarketplaceController::class, 'chooseCountry'])
    ->name('country.choose');

Route::get('/countries', [\App\Http\Controllers\CountryMarketplaceController::class, 'countriesIndex'])
    ->name('countries.choose');

Route::get('/marketplace/auto', [\App\Http\Controllers\CountryMarketplaceController::class, 'autoRoute'])
    ->name('country.auto');

Route::match(['get', 'post'], '/switch-country/{countrySlug}', [\App\Http\Controllers\CountryMarketplaceController::class, 'switchCountry'])
    ->where('countrySlug', '^(?i)(us|gb|ca|au|ie|nz)$')
    ->name('country.switch');

// Five-Country Cascading Location & GeoIP JSON APIs
Route::prefix('api/locations')->name('api.locations.')->group(function () {
    Route::get('/countries', [\App\Http\Controllers\Api\LocationApiController::class, 'indexCountries'])->name('countries.index');
    Route::get('/countries/{countryCode}/areas', [\App\Http\Controllers\Api\LocationApiController::class, 'countryAreas'])->name('countries.areas');
    Route::get('/countries/{countryCode}/areas/{areaCode}/cities', [\App\Http\Controllers\Api\LocationApiController::class, 'countryAreaCities'])->name('countries.areas.cities');
    Route::get('/detect-country', [\App\Http\Controllers\Api\LocationApiController::class, 'detectCountry'])->name('detect-country');
});

// Admin Five-Country Marketplace Management
Route::middleware(['auth', 'admin.rbac'])->prefix('admin/countries')->name('admin.countries.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdminCountryMarketplaceController::class, 'index'])->name('index');
    Route::post('/seed', [\App\Http\Controllers\Admin\AdminCountryMarketplaceController::class, 'seedGeography'])->name('seed');
    Route::get('/{countryCode}', [\App\Http\Controllers\Admin\AdminCountryMarketplaceController::class, 'show'])->name('show');
    Route::put('/{countryCode}', [\App\Http\Controllers\Admin\AdminCountryMarketplaceController::class, 'update'])->name('update');
    Route::post('/{countryCode}/categories', [\App\Http\Controllers\Admin\AdminCountryMarketplaceController::class, 'syncCategories'])->name('categories');
    Route::post('/{countryCode}/areas', [\App\Http\Controllers\Admin\AdminCountryMarketplaceController::class, 'storeArea'])->name('areas.store');
    Route::post('/{countryCode}/cities', [\App\Http\Controllers\Admin\AdminCountryMarketplaceController::class, 'storeCity'])->name('cities.store');
});

// Independent Country Marketplaces: /us, /gb, /ca, /au, /ie, /nz
Route::prefix('{countrySlug}')
    ->where(['countrySlug' => '^(?i)(us|gb|ca|au|ie|nz)$'])
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\CountryMarketplaceController::class, 'home'])
            ->name('country.home');

        Route::get('/search', [\App\Http\Controllers\CountryMarketplaceController::class, 'search'])
            ->name('country.search');

        Route::get('/sitemap.xml', [\App\Http\Controllers\CountryMarketplaceController::class, 'sitemap'])
            ->withoutMiddleware('web')
            ->name('country.sitemap');

        Route::get('/{areaSlug}', [\App\Http\Controllers\CountryMarketplaceController::class, 'showArea'])
            ->where('areaSlug', '[a-z0-9\-]+')
            ->name('country.area');

        Route::get('/{areaSlug}/{citySlug}', [\App\Http\Controllers\CountryMarketplaceController::class, 'showCity'])
            ->where(['areaSlug' => '[a-z0-9\-]+', 'citySlug' => '[a-z0-9\-]+'])
            ->name('country.city');

        Route::get('/{areaSlug}/{citySlug}/{categorySlug}', [\App\Http\Controllers\CountryMarketplaceController::class, 'showCity'])
            ->where(['areaSlug' => '[a-z0-9\-]+', 'citySlug' => '[a-z0-9\-]+', 'categorySlug' => '[a-z0-9\-]+'])
            ->name('country.city.category');
    });
