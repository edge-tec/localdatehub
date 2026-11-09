<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Search\DTOs\SearchFilterDTO;
use App\Modules\Search\Services\SearchService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingApiController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Get listings for a specific state and city.
     * GET /api/listings/location/{state}/{city}
     */
    public function locationListings(string $stateParam, string $cityParam, Request $request): JsonResponse
    {
        $state = State::where('code', strtoupper($stateParam))
            ->orWhere('slug', strtolower($stateParam))
            ->first();

        if (!$state) {
            return ApiResponse::error("State [{$stateParam}] not found.", 404, 'STATE_NOT_FOUND');
        }

        $city = City::where('state_id', $state->id)
            ->where(function ($q) use ($cityParam) {
                if (is_numeric($cityParam)) {
                    $q->where('id', (int) $cityParam);
                } else {
                    $q->where('slug', strtolower($cityParam))
                      ->orWhere('name', 'like', $cityParam);
                }
            })
            ->first();

        if (!$city) {
            return ApiResponse::error("City [{$cityParam}] not found in state [{$state->name}].", 404, 'CITY_NOT_FOUND');
        }

        $perPage = min((int) $request->query('per_page', 15), 50);
        $query = $city->listings()
            ->active()
            ->with(['category', 'state', 'primaryPhoto'])
            ->latest('published_at');

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', (int) $categoryId);
        }

        $paginator = $query->paginate($perPage);

        return ApiResponse::success([
            'state' => [
                'id' => $state->id,
                'name' => $state->name,
                'code' => $state->code,
                'slug' => $state->slug,
            ],
            'city' => [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
            ],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'listings' => $paginator->items(),
        ]);
    }

    /**
     * Search listings via API.
     * GET /api/listings/search
     */
    public function search(Request $request): JsonResponse
    {
        $filter = new SearchFilterDTO(
            query: $request->query('q'),
            categorySlug: $request->query('category'),
            subcategorySlug: $request->query('subcategory'),
            stateCode: $request->query('state'),
            cityName: $request->query('city'),
            zipCode: $request->query('zip'),
            radiusMiles: (float) $request->query('radius', 0),
            minPrice: $request->query('min_price') ? (float) $request->query('min_price') : null,
            maxPrice: $request->query('max_price') ? (float) $request->query('max_price') : null,
            listingType: $request->query('type'),
            hasPhotosOnly: (bool) $request->query('photos_only', false),
            verifiedPosterOnly: (bool) $request->query('verified_only', false),
            sortBy: $request->query('sort', 'newest')
        );

        $perPage = min((int) $request->query('per_page', 15), 50);
        $results = $this->searchService->searchListings($filter, $perPage);

        return ApiResponse::success([
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
            'listings' => $results->items(),
        ]);
    }

    /**
     * Create a new classified post via API with location validation.
     * POST /api/listings
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return ApiResponse::error('Authentication required to create listings.', 401, 'UNAUTHORIZED');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:100'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where('state_id', $request->input('state_id')),
            ],
            'price' => ['nullable', 'numeric', 'min:0'],
            'listing_type' => ['nullable', 'string', 'in:offered,wanted,service'],
            'contact_preference' => ['nullable', 'string', 'in:chat,email,phone'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $listing = Listing::create([
            'user_id' => $user->id,
            'category_id' => $validated['category_id'],
            'state_id' => $validated['state_id'],
            'city_id' => $validated['city_id'],
            'title' => $validated['title'],
            'slug' => \Illuminate\Support\Str::slug($validated['title']) . '-' . \Illuminate\Support\Str::random(6),
            'description' => $validated['description'],
            'price' => $validated['price'] ?? null,
            'listing_type' => $validated['listing_type'] ?? 'offered',
            'contact_preference' => $validated['contact_preference'] ?? 'chat',
            'phone' => $validated['phone'] ?? null,
            'status' => 'active',
            'published_at' => now(),
        ]);

        return ApiResponse::success([
            'message' => 'Listing created successfully.',
            'listing' => $listing->load(['state', 'city', 'category']),
        ], 201);
    }
}

