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

        // Dynamic SMTP configuration from persistent SystemSetting
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                $mailSettings = \App\Modules\Admin\Models\SystemSetting::where('group', 'smtp')->pluck('value', 'key');
                if ($mailSettings->has('mail_host') && !empty($mailSettings->get('mail_host'))) {
                    $enc = $mailSettings->get('mail_encryption', 'tls');
                    config([
                        'mail.default' => $mailSettings->get('mail_mailer', 'smtp'),
                        'mail.mailers.smtp.transport' => 'smtp',
                        'mail.mailers.smtp.host' => $mailSettings->get('mail_host'),
                        'mail.mailers.smtp.port' => (int) ($mailSettings->get('mail_port', 587)),
                        'mail.mailers.smtp.encryption' => ($enc === 'none' || empty($enc)) ? null : $enc,
                        'mail.mailers.smtp.username' => $mailSettings->get('mail_username'),
                        'mail.mailers.smtp.password' => $mailSettings->get('mail_password'),
                        'mail.mailers.smtp.verify_peer' => false,
                        'mail.from.address' => $mailSettings->get('mail_from_address', config('mail.from.address')),
                        'mail.from.name' => $mailSettings->get('mail_from_name', config('mail.from.name')),
                    ]);
                }
            }
        } catch (\Throwable) {
            // Fail-safe during early bootstrapping, migrations, or CLI commands
        }
    }
}
