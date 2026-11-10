<?php

namespace App\Modules\SEO\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\SEO\DTOs\MetaTagsDTO;

class LocalSeoService
{
    protected string $brandName = 'LocalDateHub';

    /**
     * Generate localized SEO metadata for State or City landing page.
     */
    public function generateLocationMeta(?State $state, ?City $city = null, ?Category $category = null, int $page = 1): MetaTagsDTO
    {
        $hasActiveListings = true;

        if ($city && $state) {
            $query = Listing::where('status', Listing::STATUS_ACTIVE)->where('city_id', $city->id);
            if ($category) {
                $query->where('category_id', $category->id);
            }
            $activeCount = $query->count();
            $hasActiveListings = ($activeCount > 0);

            $catPrefix = $category ? "{$category->name} in " : 'Adult Personals & Dating in ';
            $title = "{$catPrefix}{$city->name}, {$state->code} | {$this->brandName}";
            $description = "Browse verified {$catPrefix}{$city->name}, {$state->name}. Find active local singles, personal ads, and direct connections in {$city->name}.";
            $canonical = route('locations.city', ['stateCode' => strtolower($state->code), 'citySlug' => $city->slug]);

            $breadcrumbs = [
                ['name' => 'Home', 'url' => url('/')],
                ['name' => $state->name, 'url' => route('locations.state', strtolower($state->code))],
                ['name' => $city->name, 'url' => $canonical],
            ];

            if ($category) {
                $breadcrumbs[] = ['name' => $category->name, 'url' => route('locations.city.category', ['stateCode' => strtolower($state->code), 'citySlug' => $city->slug, 'categorySlug' => $category->slug])];
            }
        } elseif ($state) {
            $activeCount = Listing::where('status', Listing::STATUS_ACTIVE)->where('state_id', $state->id)->count();
            $hasActiveListings = ($activeCount > 0);

            $title = "Adult Personals & Dating in {$state->name} ({$state->code}) | {$this->brandName}";
            $description = "Connect with singles and adult personal advertisers across {$state->name}. Browse cities, categories, and verified local listings.";
            $canonical = route('locations.state', strtolower($state->code));

            $breadcrumbs = [
                ['name' => 'Home', 'url' => url('/')],
                ['name' => $state->name, 'url' => $canonical],
            ];
        } else {
            $title = "USA Adult Personals & Classifieds by State | {$this->brandName}";
            $description = "Browse adult personals and dating ads across all 50 US states and major metropolitan cities on {$this->brandName}.";
            $canonical = route('locations.index');
            $breadcrumbs = [
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Locations', 'url' => $canonical],
            ];
        }

        // Apply strict Google Quality guideline: noindex thin/empty location pages to prevent soft 404 / doorway page penalties
        $robots = ($hasActiveListings && $page === 1) ? 'index, follow' : 'noindex, follow';

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Place',
            'name' => $city ? "{$city->name}, {$state?->name}" : ($state ? $state->name : 'United States'),
            'address' => [
                '@type' => 'PostalAddress',
                'addressRegion' => $state?->code ?? 'US',
                'addressCountry' => 'US',
            ],
        ];

        return new MetaTagsDTO(
            title: $title,
            description: $description,
            canonicalUrl: $canonical,
            ogImage: asset('images/og-localdatehub.jpg'),
            ogType: 'website',
            robots: $robots,
            city: $city?->name,
            state: $state?->code,
            category: $category?->name,
            structuredData: $structuredData,
            breadcrumbs: $breadcrumbs
        );
    }
}

