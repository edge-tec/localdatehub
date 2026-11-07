<?php

namespace App\Modules\Search\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\DTOs\CoordinateDTO;
use App\Modules\Locations\Services\LocationService;
use App\Modules\Search\DTOs\SearchFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    /**
     * Perform structured search over active classified listings.
     */
    public function searchListings(SearchFilterDTO $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = Listing::query()
            ->active()
            ->with(['category', 'city.state', 'state', 'primaryPhoto']);

        // Keyword search in title & description
        if (!empty($filter->query)) {
            $keyword = $filter->query;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // Category filter (including child subcategories)
        if (!empty($filter->categorySlug)) {
            $category = Category::where('slug', $filter->categorySlug)->first();
            if ($category) {
                $categoryIds = $category->descendantIds(true);
                $query->whereIn('category_id', $categoryIds);
            }
        }

        // City filter
        if (!empty($filter->cityName)) {
            $cityName = $filter->cityName;
            $query->whereHas('city', function ($q) use ($cityName) {
                $q->where('name', 'like', "%{$cityName}%");
            });
        }

        // State filter
        if (!empty($filter->stateCode)) {
            $stateCode = $filter->stateCode;
            $query->whereHas('state', function ($q) use ($stateCode) {
                $q->where('code', $stateCode);
            });
        }

        // Photos only
        if ($filter->withPhotosOnly) {
            $query->where('has_photos', true);
        }

        // Verified poster only
        if ($filter->verifiedOnly) {
            $query->where('is_verified_poster', true);
        }

        // Age filter
        if ($filter->minAge > 18 || $filter->maxAge < 99) {
            $query->whereBetween('poster_age', [$filter->minAge, $filter->maxAge]);
        }

        // Radius coordinates filter if provided
        if ($filter->latitude !== null && $filter->longitude !== null) {
            $origin = new CoordinateDTO($filter->latitude, $filter->longitude);
            $boundingBox = $this->locationService->calculateBoundingBox($origin, $filter->radiusKm);

            $query->whereHas('location', function ($q) use ($boundingBox) {
                $q->whereBetween('fuzzed_latitude', [$boundingBox['minLat'], $boundingBox['maxLat']])
                  ->whereBetween('fuzzed_longitude', [$boundingBox['minLng'], $boundingBox['maxLng']]);
            });
        }

        // Sort: featured listings priority first, then chosen sort
        $query->orderByDesc('is_featured');

        switch ($filter->sort) {
            case 'views':
                $query->orderByDesc('view_count')->orderByDesc('id');
                break;
            case 'oldest':
                $query->orderBy('published_at')->orderBy('id');
                break;
            case 'latest':
            default:
                $query->orderByDesc('published_at')->orderByDesc('id');
                break;
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
