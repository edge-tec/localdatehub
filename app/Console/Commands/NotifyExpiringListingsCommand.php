<?php

namespace App\Console\Commands;

use App\Modules\Listings\Models\Listing;
use App\Modules\Notifications\Notifications\ListingExpiringNotification;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Console\Command;

class NotifyExpiringListingsCommand extends Command
{
    protected $signature = 'listings:notify-expiring';
    protected $description = 'Notify advertisers whose listings are expiring in 3 days or 1 day';

    public function handle(NotificationService $notificationService): int
    {
        $this->info('Checking for expiring listings...');

        // 1. Listings expiring in 3 days
        $expiringIn3Days = Listing::where('status', Listing::STATUS_ACTIVE)
            ->whereBetween('expires_at', [now()->addDays(2)->endOfDay(), now()->addDays(3)->endOfDay()])
            ->with('user')
            ->get();

        $count = 0;
        foreach ($expiringIn3Days as $listing) {
            if ($listing->user) {
                $notificationService->send(
                    $listing->user,
                    new ListingExpiringNotification($listing, 3),
                    'listing_expiring'
                );
                $count++;
            }
        }

        // 2. Listings expiring in 1 day
        $expiringIn1Day = Listing::where('status', Listing::STATUS_ACTIVE)
            ->whereBetween('expires_at', [now(), now()->addDay()->endOfDay()])
            ->with('user')
            ->get();

        foreach ($expiringIn1Day as $listing) {
            if ($listing->user) {
                $notificationService->send(
                    $listing->user,
                    new ListingExpiringNotification($listing, 1),
                    'listing_expiring'
                );
                $count++;
            }
        }

        $this->info("Dispatched {$count} expiring listing notifications.");
        return self::SUCCESS;
    }
}
