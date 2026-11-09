<?php

namespace App\Modules\Search\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\DTOs\CoordinateDTO;
use App\Modules\Locations\Services\LocationService;
use App\Modules\Search\DTOs\SearchFilterDTO;
use App\Modules\Search\Drivers\DatabaseSearchDriver;
use App\Modules\Search\Drivers\MeilisearchDriver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class SearchService
{
    public function __construct(
        protected DatabaseSearchDriver $databaseDriver,
        protected MeilisearchDriver $meilisearchDriver,
        protected LocationService $locationService
    ) {}

    /**
     * Compute distance and filter matching listings in a distance radius.
     */
    public function isWithinRadius(SearchFilterDTO $filter, float $itemLat, float $itemLng): bool
    {
        if ($filter->latitude === null || $filter->longitude === null) {
            return true;
        }

        $origin = new CoordinateDTO($filter->latitude, $filter->longitude);
        $target = new CoordinateDTO($itemLat, $itemLng);

        $distance = $this->locationService->calculateDistanceKm($origin, $target);
        return $distance <= $filter->radiusKm;
    }

    /**
     * Perform structured search using Meilisearch if available, with transparent database fallback.
     */
    public function searchListings(SearchFilterDTO $filter, int $perPage = 15): LengthAwarePaginator
    {
        if ($this->meilisearchDriver->isAvailable()) {
            try {
                $results = $this->meilisearchDriver->search($filter, $perPage);
                if ($results !== null) {
                    return $results;
                }
            } catch (Throwable $e) {
                Log::warning('Meilisearch search failure: ' . $e->getMessage() . '. Falling back to database engine.');
            }
        }

        return $this->databaseDriver->search($filter, $perPage);
    }

    /**
     * Calculate listing counts per active category group for faceted search navigation.
     *
     * @return array<string, int>
     */
    public function getCategoryCounts(SearchFilterDTO $filter): array
    {
        $query = Listing::query()->active();

        // Apply location filtering to count estimates
        if (!empty($filter->stateCode)) {
            $stateCode = $filter->stateCode;
            $query->whereHas('state', fn ($q) => $q->where('code', $stateCode));
        }

        if (!empty($filter->cityName)) {
            $cityName = $filter->cityName;
            $stateCode = $filter->stateCode;
            $query->whereHas('city', function ($q) use ($cityName, $stateCode) {
                $q->where('name', 'like', "%{$cityName}%");
                if (!empty($stateCode)) {
                    $q->whereHas('state', fn ($st) => $st->where('code', $stateCode));
                }
            });
        }

        $counts = $query->selectRaw('category_id, count(*) as count')
            ->groupBy('category_id')
            ->pluck('count', 'category_id')
            ->all();

        // Aggregate counts up to parent categories
        $categories = Category::with('children')->get();
        $aggregated = [];

        foreach ($categories as $cat) {
            $descendantIds = $cat->descendantIds(true);
            $sum = 0;
            foreach ($descendantIds as $id) {
                $sum += ($counts[$id] ?? 0);
            }
            $aggregated[$cat->slug] = $sum;
        }

        return $aggregated;
    }

    /**
     * Index a listing across configured search drivers.
     */
    public function indexListing(Listing $listing): bool
    {
        return $this->meilisearchDriver->indexListing($listing);
    }

    /**
     * Remove a listing from search indexes.
     */
    public function removeFromIndex(int $listingId): bool
    {
        return $this->meilisearchDriver->removeListing($listingId);
    }

    /**
     * Return active driver identifier.
     */
    public function getActiveDriverName(): string
    {
        return $this->meilisearchDriver->isAvailable() ? 'meilisearch' : 'database';
    }
}
