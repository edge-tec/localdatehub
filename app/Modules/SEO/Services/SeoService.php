<?php

namespace App\Modules\SEO\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\SEO\DTOs\MetaTagsDTO;
use App\Modules\SEO\Models\SeoMeta;
use Illuminate\Support\Str;

class SeoService
{
    protected string $brandName = 'LocalDateHub';

    /**
     * Generate metadata for home / index.
     */
    public function generateForHome(): MetaTagsDTO
    {
        $custom = SeoMeta::where('page_route', 'home')->first();
        $title = $custom?->meta_title ?: "{$this->brandName} — USA Adult Personal Classified Listings & Dating";
        $description = $custom?->meta_description ?: "The premier privacy-conscious classified ads platform connecting consenting adults across all 50 states for lawful personal, dating, and companionship connections.";
        $canonical = url('/');

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->brandName,
            'url' => $canonical,
            'description' => $description,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/search') . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];

        return new MetaTagsDTO(
            title: $title,
            description: $description,
            canonicalUrl: $canonical,
            ogImage: asset('images/og-localadzone.jpg'),
            ogType: 'website',
            robots: 'index, follow',
            structuredData: $structuredData,
            breadcrumbs: [
                ['name' => 'Home', 'url' => url('/')],
            ]
        );
    }

    /**
     * Generate metadata for individual listing detail page.
     */
    public function generateForListing(Listing $listing): MetaTagsDTO
    {
        $city = $listing->city?->name;
        $state = $listing->state?->code;
        $cat = $listing->category?->name;

        $locationSuffix = $city && $state ? "in {$city}, {$state}" : ($state ? "in {$state}" : "USA");
        $title = "{$listing->title} — {$locationSuffix} | {$this->brandName}";

        // Clean snippet without PII for description
        $rawDescription = strip_tags($listing->description);
        $description = Str::limit(preg_replace('/\s+/', ' ', $rawDescription), 155);

        $canonical = route('listings.show', $listing->slug);
        $imageUrl = $listing->primaryPhoto ? asset('storage/' . $listing->primaryPhoto->file_path) : asset('images/og-localadzone.jpg');

        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => $cat ?: 'Personals', 'url' => $listing->category ? route('categories.show', $listing->category->slug) : url('/search')],
            ['name' => $listing->title, 'url' => $canonical],
        ];

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemPage',
            'name' => $listing->title,
            'description' => $description,
            'url' => $canonical,
            'datePublished' => $listing->published_at?->toIso8601String() ?: $listing->created_at->toIso8601String(),
            'breadcrumb' => $this->formatBreadcrumbSchema($breadcrumbs),
        ];

        return new MetaTagsDTO(
            title: $title,
            description: $description,
            canonicalUrl: $canonical,
            ogImage: $imageUrl,
            ogType: 'article',
            robots: 'index, follow',
            city: $city,
            state: $state,
            category: $cat,
            structuredData: $structuredData,
            breadcrumbs: $breadcrumbs
        );
    }

    /**
     * Generate metadata for Category pages.
     */
    public function generateForCategory(Category $category, int $page = 1): MetaTagsDTO
    {
        $title = "{$category->name} Adult Personals | {$this->brandName}";
        if ($page > 1) {
            $title .= " (Page {$page})";
        }

        $description = "Browse verified {$category->name} personal ads, dating listings, and activity partners. Safe, private adult classifieds.";
        $canonical = route('categories.show', $category->slug);

        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Categories', 'url' => url('/categories')],
            ['name' => $category->name, 'url' => $canonical],
        ];

        return new MetaTagsDTO(
            title: $title,
            description: $description,
            canonicalUrl: $canonical,
            ogImage: asset('images/og-localadzone.jpg'),
            ogType: 'website',
            robots: $page > 1 ? 'noindex, follow' : 'index, follow',
            category: $category->name,
            structuredData: [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $title,
                'description' => $description,
                'breadcrumb' => $this->formatBreadcrumbSchema($breadcrumbs),
            ],
            breadcrumbs: $breadcrumbs
        );
    }

    /**
     * Generate metadata for State & City Landing Pages.
     */
    public function generateForLocation(?State $state, ?City $city = null, int $page = 1): MetaTagsDTO
    {
        if ($city && $state) {
            $title = "Adult Personals & Dating in {$city->name}, {$state->code} | {$this->brandName}";
            $description = "Find local adult personal ads in {$city->name}, {$state->name}. Browse verified classifieds, dating listings, and local companions.";
            $canonical = route('locations.city', ['stateCode' => strtolower($state->code), 'citySlug' => $city->slug]);
            $breadcrumbs = [
                ['name' => 'Home', 'url' => url('/')],
                ['name' => $state->name, 'url' => route('locations.state', ['stateCode' => strtolower($state->code)])],
                ['name' => $city->name, 'url' => $canonical],
            ];
        } elseif ($state) {
            $title = "{$state->name} Adult Personals & Classifieds ({$state->code}) | {$this->brandName}";
            $description = "Connect with consenting adults in {$state->name}. Browse verified personal classifieds in Austin, Dallas, Houston, and surrounding cities.";
            $canonical = route('locations.state', ['stateCode' => strtolower($state->code)]);
            $breadcrumbs = [
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Locations', 'url' => url('/locations')],
                ['name' => $state->name, 'url' => $canonical],
            ];
        } else {
            return $this->generateForHome();
        }

        if ($page > 1) {
            $title .= " (Page {$page})";
        }

        return new MetaTagsDTO(
            title: $title,
            description: $description,
            canonicalUrl: $canonical,
            ogImage: asset('images/og-localadzone.jpg'),
            ogType: 'website',
            robots: $page > 1 ? 'noindex, follow' : 'index, follow',
            city: $city?->name,
            state: $state?->code,
            structuredData: [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $title,
                'description' => $description,
                'breadcrumb' => $this->formatBreadcrumbSchema($breadcrumbs),
            ],
            breadcrumbs: $breadcrumbs
        );
    }

    /**
     * Format Schema.org BreadcrumbList JSON-LD array.
     */
    public function formatBreadcrumbSchema(array $breadcrumbs): array
    {
        $items = [];
        foreach ($breadcrumbs as $idx => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $idx + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }
}