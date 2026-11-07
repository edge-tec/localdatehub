<?php

namespace App\Modules\Search\Contracts;

use App\Modules\Listings\Models\Listing;
use App\Modules\Search\DTOs\SearchFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SearchDriverInterface
{
    /**
     * Get the name identifier of this search driver.
     */
    public function getName(): string;

    /**
     * Check if the search driver service is configured and reachable.
     */
    public function isAvailable(): bool;

    /**
     * Execute search query and return paginated listing results.
     */
    public function search(SearchFilterDTO $filter, int $perPage = 15): ?LengthAwarePaginator;

    /**
     * Index a single published listing into the search engine.
     */
    public function indexListing(Listing $listing): bool;

    /**
     * Remove a listing from the search index.
     */
    public function removeListing(int $listingId): bool;
}
