<?php

namespace App\Modules\Search\Jobs;

use App\Modules\Listings\Models\Listing;
use App\Modules\Search\Services\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncListingToSearchIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public int $listingId
    ) {}

    public function handle(SearchService $searchService): void
    {
        try {
            $listing = Listing::withTrashed()->find($this->listingId);

            if (!$listing || $listing->trashed() || !$listing->isActive()) {
                $searchService->removeFromIndex($this->listingId);
                return;
            }

            $searchService->indexListing($listing);
        } catch (Throwable $e) {
            Log::warning("SyncListingToSearchIndexJob failed for listing #{$this->listingId}: " . $e->getMessage());
            // Do not rethrow if it is a driver connection issue to prevent queue backlogs
        }
    }
}

