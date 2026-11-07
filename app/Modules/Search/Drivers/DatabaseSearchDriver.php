<?php

namespace App\Modules\Search\Drivers;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\DTOs\CoordinateDTO;
use App\Modules\Locations\Models\ZipCode;
use App\Modules\Locations\Services\LocationService;
use App\Modules\Search\Contracts\SearchDriverInterface;
use App\Modules\Search\DTOs\SearchFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DatabaseSearchDriver implements SearchDriverInterface
{
    public function __construct(
        protected LocationService $locationService
    ) {}

    public function getName(): string
    {
        return 'database';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function search(SearchFilterDTO $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = Listing::query()
            ->active()
            ->with(['category.parent', 'city.state', 'state', 'primaryPhoto', 'location']);

        // 1. Text Keyword Search (title & description)
        if (!empty($filter->query)) {
            $keyword = $filter->query;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 2. Category & Subcategory Filter
        if (!empty($filter->subcategorySlug)) {
            $subcategory = Category::where('slug', $filter->subcategorySlug)->first();
            if ($subcategory) {
                $query->where('category_id', $subcategory->id);
            }
        } elseif (!empty($filter->categorySlug)) {
            $category = Category::where('slug', $filter->categorySlug)->first();
            if ($category) {
                $categoryIds = $category->descendantIds(true);
                $query->whereIn('category_id', $categoryIds);
            }
        }

        // 3. State Filter
        if (!empty($filter->stateCode)) {
            $stateCode = $filter->stateCode;
            $query->whereHas('state', function ($q) use ($stateCode) {
                $q->where('code', $stateCode);
            });
        }

        // 4. City Filter
        if (!empty($filter->cityName)) {
            $cityName = $filter->cityName;
            $query->whereHas('city', function ($q) use ($cityName) {
                $q->where('name', 'like', "%{$cityName}%");
            });
        }

        // 5. ZIP Code & Distance Radius Geocoding
        $resolvedLat = $filter->latitude;
        $resolvedLng = $filter->longitude;

        if (!empty($filter->zipCode)) {
            $zipRecord = ZipCode::where('zip_code', $filter->zipCode)->first();
            if ($zipRecord) {
                if ($zipRecord->latitude && $zipRecord->longitude) {
                    $resolvedLat = $zipRecord->latitude;
                    $resolvedLng = $zipRecord->longitude;
                } else {
                    $query->where('zip_code_id', $zipRecord->id);
                }
            } else {
                $zipCode = $filter->zipCode;
                $query->whereHas('zipCode', function ($q) use ($zipCode) {
                    $q->where('zip_code', $zipCode);
                });
            }
        }

        // 6. Distance Radius / Bounding Box Spatial Filtering
        if ($resolvedLat !== null && $resolvedLng !== null) {
            $origin = new CoordinateDTO($resolvedLat, $resolvedLng);
            $boundingBox = $this->locationService->calculateBoundingBox($origin, $filter->radiusKm);

            $query->where(function ($q) use ($boundingBox) {
                $q->whereHas('location', function ($lq) use ($boundingBox) {
                    $lq->whereBetween('fuzzed_latitude', [$boundingBox['minLat'], $boundingBox['maxLat']])
                       ->whereBetween('fuzzed_longitude', [$boundingBox['minLng'], $boundingBox['maxLng']]);
                });
            });
        }

        // 7. Dynamic Listing Attributes (EAV & Core)
        if (!empty($filter->attributes)) {
            foreach ($filter->attributes as $code => $value) {
                if ($code === 'intent') {
                    $query->where('intent', $value);
                } elseif ($code === 'poster_gender') {
                    $query->where('poster_gender', $value);
                } else {
                    $query->whereHas('attributes', function ($aq) use ($code, $value) {
                        $aq->where('attribute_code', $code)
                           ->where(function ($sq) use ($value) {
                               $sq->where('value_text', $value)
                                  ->orWhere('value_number', is_numeric($value) ? $value : null);
                           });
                    });
                }
            }
        }

        // 8. Published Date Timeframe
        if ($filter->publishedDate === 'today') {
            $query->where('published_at', '>=', now()->subDay());
        } elseif ($filter->publishedDate === '7d') {
            $query->where('published_at', '>=', now()->subDays(7));
        } elseif ($filter->publishedDate === '30d') {
            $query->where('published_at', '>=', now()->subDays(30));
        }

        // 9. Featured Status Flag
        if ($filter->featuredOnly) {
            $query->where('is_featured', true);
        }

        // 10. Photo Availability
        if ($filter->withPhotosOnly) {
            $query->where('has_photos', true);
        }

        // 11. Poster Verification Status
        if ($filter->verifiedOnly) {
            $query->where('is_verified_poster', true);
        }

        // 12. Poster Age Boundaries
        if ($filter->minAge > 18 || $filter->maxAge < 99) {
            $query->whereBetween('poster_age', [$filter->minAge, $filter->maxAge]);
        }

        // Transparent Ranking Rules:
        // Rule 1: Featured listings are positioned with high priority
        $query->orderByDesc('is_featured');

        // Rule 2: Secondary sort selection
        switch ($filter->sort) {
            case 'oldest':
                $query->orderBy('published_at')->orderBy('id');
                break;
            case 'updated':
                $query->orderByRaw('COALESCE(renewed_at, updated_at) DESC')->orderByDesc('id');
                break;
            case 'views':
                $query->orderByDesc('view_count')->orderByDesc('id');
                break;
            case 'newest':
            default:
                $query->orderByDesc('published_at')->orderByDesc('id');
                break;
        }

        return $query->paginate($perPage, ['*'], 'page', $filter->page)->withQueryString();
    }

    public function indexListing(Listing $listing): bool
    {
        return true;
    }

    public function removeListing(int $listingId): bool
    {
        return true;
    }
}

