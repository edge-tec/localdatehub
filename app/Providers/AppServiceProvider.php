<?php

namespace App\Providers;

use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Contact\Policies\ContactMessagePolicy;
use App\Modules\Listings\Models\Listing;
use App\Modules\Search\Observers\ListingSearchObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Listing::observe(ListingSearchObserver::class);
        Gate::policy(ContactMessage::class, ContactMessagePolicy::class);
    }
}
