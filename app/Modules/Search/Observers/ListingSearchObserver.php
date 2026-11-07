<?php

namespace App\Modules\Search\Observers;

use App\Modules\Listings\Models\Listing;
use App\Modules\Search\Jobs\RemoveListingFromSearchIndexJob;
use App\Modules\Search\Jobs\SyncListingToSearchIndexJob;

class ListingSearchObserver
{
    /**
     * Handle the Listing "saved" event.
     */
    public function saved(Listing $listing): void
    {
        if ($listing->isActive() && !$listing->trashed()) {
            SyncListingToSearchIndexJob::dispatch($listing->id);
        } else {
            RemoveListingFromSearchIndexJob::dispatch($listing->id);
        }
    }

    /**
     * Handle the Listing "deleted" event.
     */
    public function deleted(Listing $listing): void
    {
        RemoveListingFromSearchIndexJob::dispatch($listing->id);
    }

    /**
     * Handle the Listing "restored" event.
     */
    public function restored(Listing $listing): void
    {
        if ($listing->isActive()) {
            SyncListingToSearchIndexJob::dispatch($listing->id);
        }
    }

    /**
     * Handle the Listing "forceDeleted" event.
     */
    public function forceDeleted(Listing $listing): void
    {
        RemoveListingFromSearchIndexJob::dispatch($listing->id);
    }
}

