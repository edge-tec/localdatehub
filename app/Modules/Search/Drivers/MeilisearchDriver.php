<?php

namespace App\Modules\Search\Drivers;

use App\Modules\Listings\Models\Listing;
use App\Modules\Search\Contracts\SearchDriverInterface;
use App\Modules\Search\DTOs\SearchFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MeilisearchDriver implements SearchDriverInterface
{
    protected ?string $host;
    protected ?string $key;
    protected string $index;
    protected float $timeout;

    public function __construct()
    {
        $this->host = config('services.meilisearch.host');
        $this->key = config('services.meilisearch.key');
        $this->index = config('services.meilisearch.index', 'listings');
        $this->timeout = (float) config('services.meilisearch.timeout', 2.0);
    }

    public function getName(): string
    {
        return 'meilisearch';
    }

    public function isAvailable(): bool
    {
        if (empty($this->host)) {
            return false;
        }

        try {
            $response = Http::timeout(0.8)->get(rtrim($this->host, '/') . '/health');
            return $response->successful() && ($response->json('status') === 'available');
        } catch (Throwable $e) {
            return false;
        }
    }

    public function search(SearchFilterDTO $filter, int $perPage = 15): ?LengthAwarePaginator
    {
        if (empty($this->host)) {
            return null;
        }

        try {
            $endpoint = rtrim($this->host, '/') . '/indexes/' . urlencode($this->index) . '/search';

            $filters = [];
            // Security: strictly only active listings
            $filters[] = 'status = "active"';

            if (!empty($filter->categorySlug)) {
                $filters[] = 'category_slug = "' . addslashes($filter->categorySlug) . '"';
            }

            if (!empty($filter->stateCode)) {
                $filters[] = 'state_code = "' . addslashes($filter->stateCode) . '"';
            }

            if (!empty($filter->cityName)) {
                $filters[] = 'city_name = "' . addslashes($filter->cityName) . '"';
            }

            if (!empty($filter->zipCode)) {
                $filters[] = 'zip_code = "' . addslashes($filter->zipCode) . '"';
            }

            if ($filter->featuredOnly) {
                $filters[] = 'is_featured = true';
            }

            if ($filter->withPhotosOnly) {
                $filters[] = 'has_photos = true';
            }

            if ($filter->verifiedOnly) {
                $filters[] = 'is_verified_poster = true';
            }

            if ($filter->minAge > 18 || $filter->maxAge < 99) {
                $filters[] = "poster_age >= {$filter->minAge} AND poster_age <= {$filter->maxAge}";
            }

            $sortRules = ['is_featured:desc'];
            switch ($filter->sort) {
                case 'oldest':
                    $sortRules[] = 'published_at:asc';
                    break;
                case 'updated':
                    $sortRules[] = 'updated_at:desc';
                    break;
                case 'views':
                    $sortRules[] = 'view_count:desc';
                    break;
                case 'newest':
                default:
                    $sortRules[] = 'published_at:desc';
                    break;
            }

            $payload = [
                'q' => (string) ($filter->query ?? ''),
                'filter' => implode(' AND ', $filters),
                'sort' => $sortRules,
                'offset' => ($filter->page - 1) * $perPage,
                'limit' => $perPage,
            ];

            $request = Http::timeout($this->timeout);
            if (!empty($this->key)) {
                $request = $request->withToken($this->key);
            }

            $response = $request->post($endpoint, $payload);

            if (!$response->successful()) {
                Log::warning('Meilisearch search error: ' . $response->body());
                return null;
            }

            $data = $response->json();
            $hits = $data['hits'] ?? [];
            $totalHits = (int) ($data['estimatedTotalHits'] ?? count($hits));

            $ids = array_column($hits, 'id');
            if (empty($ids)) {
                return new ConcretePaginator([], 0, $perPage, $filter->page, [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]);
            }

            // Hydrate models in matching order with eager loading
            $models = Listing::query()
                ->active()
                ->with(['category.parent', 'city.state', 'state', 'primaryPhoto', 'location', 'ownerIdentityVerification'])
                ->withCount('media')
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');

            $sortedListings = collect($ids)
                ->map(fn ($id) => $models->get($id))
                ->filter()
                ->values();

            return new ConcretePaginator($sortedListings, $totalHits, $perPage, $filter->page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Meilisearch communication exception: ' . $e->getMessage() . '. Falling back to database engine.');
            return null;
        }
    }

    public function indexListing(Listing $listing): bool
    {
        if (empty($this->host)) {
            return false;
        }

        try {
            // Strictly exclude non-active or deleted listings from search index
            if (!$listing->isActive() || $listing->trashed()) {
                return $this->removeListing($listing->id);
            }

            $listing->loadMissing(['category', 'state', 'city', 'zipCode', 'location']);

            // SANITIZATION: Never expose private user details through search index!
            $document = [
                'id' => $listing->id,
                'title' => $listing->title,
                'slug' => $listing->slug,
                'description' => strip_tags($listing->description),
                'category_id' => $listing->category_id,
                'category_slug' => $listing->category?->slug,
                'state_code' => $listing->state?->code,
                'city_name' => $listing->city?->name,
                'zip_code' => $listing->zipCode?->zip_code,
                'poster_age' => $listing->poster_age,
                'poster_gender' => $listing->poster_gender,
                'intent' => $listing->intent,
                'status' => $listing->status,
                'is_featured' => (bool) $listing->is_featured,
                'is_verified_poster' => (bool) $listing->is_verified_poster,
                'has_photos' => (bool) $listing->has_photos,
                'view_count' => (int) $listing->view_count,
                'published_at' => $listing->published_at?->timestamp,
                'updated_at' => $listing->updated_at?->timestamp,
                'expires_at' => $listing->expires_at?->timestamp,
            ];

            if ($listing->location?->fuzzed_latitude && $listing->location?->fuzzed_longitude) {
                $document['_geo'] = [
                    'lat' => $listing->location->fuzzed_latitude,
                    'lng' => $listing->location->fuzzed_longitude,
                ];
            }

            $endpoint = rtrim($this->host, '/') . '/indexes/' . urlencode($this->index) . '/documents';
            $request = Http::timeout($this->timeout);
            if (!empty($this->key)) {
                $request = $request->withToken($this->key);
            }

            $response = $request->post($endpoint, [$document]);
            return $response->successful();
        } catch (Throwable $e) {
            Log::warning("Failed to index listing {$listing->id} to Meilisearch: " . $e->getMessage());
            return false;
        }
    }

    public function removeListing(int $listingId): bool
    {
        if (empty($this->host)) {
            return false;
        }

        try {
            $endpoint = rtrim($this->host, '/') . '/indexes/' . urlencode($this->index) . '/documents/' . $listingId;
            $request = Http::timeout($this->timeout);
            if (!empty($this->key)) {
                $request = $request->withToken($this->key);
            }

            $response = $request->delete($endpoint);
            return $response->successful();
        } catch (Throwable $e) {
            Log::warning("Failed to remove listing {$listingId} from Meilisearch: " . $e->getMessage());
            return false;
        }
    }
}

