<?php

namespace App\Modules\Search\Services;

use App\Modules\Locations\DTOs\CoordinateDTO;
use App\Modules\Locations\Services\LocationService;
use App\Modules\Search\DTOs\SearchFilterDTO;

class SearchService
{
    public function __construct(
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
}
