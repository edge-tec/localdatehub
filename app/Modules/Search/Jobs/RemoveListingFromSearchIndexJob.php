<?php

namespace App\Modules\Search\Jobs;

use App\Modules\Search\Services\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RemoveListingFromSearchIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $listingId
    ) {}

    public function handle(SearchService $searchService): void
    {
        try {
            $searchService->removeFromIndex($this->listingId);
        } catch (Throwable $e) {
            Log::warning("RemoveListingFromSearchIndexJob failed for listing #{$this->listingId}: " . $e->getMessage());
        }
    }
}
