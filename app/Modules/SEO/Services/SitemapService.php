<?php

namespace App\Modules\SEO\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\SEO\Models\CmsPage;
use App\Modules\SEO\Models\SeoSitemap;
use App\Modules\SEO\Models\SeoUrlRecord;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class SitemapService
{
    /**
     * Generate all sitemaps and update registry.
     */
    public function generateAll(): array
    {
        $results = [
            'pages' => $this->generatePagesSitemap(),
            'categories' => $this->generateCategoriesSitemap(),
            'locations' => $this->generateLocationsSitemap(),
            'states' => $this->generateStatesSitemap(),
            'cities' => $this->generateCitiesSitemap(),
            'listings' => $this->generateListingsSitemap(),
            'images' => $this->generateImagesSitemap(),
        ];

        $results['index'] = $this->generateMasterIndex();

        return $results;
    }

    /**
     * Master Sitemap Index.
     */
    public function generateMasterIndex(): SeoSitemap
    {
        $subSitemaps = [
            route('sitemaps.pages'),
            route('sitemaps.categories'),
            route('sitemaps.locations'),
            route('sitemaps.states'),
            route('sitemaps.cities'),
            route('sitemaps.listings'),
            route('sitemaps.images'),
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($subSitemaps as $url) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>" . now()->toAtomString() . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        return $this->recordSitemap('sitemap_index.xml', $xml, count($subSitemaps));
    }

    /**
     * Pages & CMS Sitemap.
     */
    public function generatePagesSitemap(): SeoSitemap
    {
        $urls = [
            [
                'loc' => url('/'),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'hourly',
                'priority' => '1.0',
            ],
            [
                'loc' => url('/search'),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'hourly',
                'priority' => '0.9',
            ],
            [
                'loc' => url('/about'),
                'lastmod' => now()->subDays(7)->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
            [
                'loc' => url('/terms'),
                'lastmod' => now()->subDays(30)->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
            [
                'loc' => url('/privacy'),
                'lastmod' => now()->subDays(30)->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
            [
                'loc' => url('/safety'),
                'lastmod' => now()->subDays(14)->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.4',
            ],
            [
                'loc' => url('/pricing'),
                'lastmod' => now()->subDays(7)->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
        ];

        $cms = CmsPage::where('is_published', true)->get();
        foreach ($cms as $page) {
            $url = url('/' . ltrim($page->slug, '/'));
            $urls[] = [
                'loc' => $url,
                'lastmod' => $page->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
            $this->syncUrlRecord($url, 'cms_page', $page->title, $page->meta_description, $page);
        }

        $xml = $this->buildUrlsetXml($urls);
        return $this->recordSitemap('pages-sitemap.xml', $xml, count($urls));
    }

    /**
     * Categories Sitemap.
     */
    public function generateCategoriesSitemap(): SeoSitemap
    {
        $urls = [];
        $categories = Category::where('is_active', true)->get();

        foreach ($categories as $cat) {
            $url = route('categories.show', $cat->slug);
            $urls[] = [
                'loc' => $url,
                'lastmod' => $cat->updated_at->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ];
            $this->syncUrlRecord($url, 'category', "{$cat->name} Adult Personals", $cat->description, $cat);
        }

        $xml = $this->buildUrlsetXml($urls);
        return $this->recordSitemap('categories-sitemap.xml', $xml, count($urls));
    }

    /**
     * Dedicated Locations Hub and Local SEO City+Category Sitemap (Phase 4).
     * Prevents URL duplication with states-sitemap and cities-sitemap.
     */
    public function generateLocationsSitemap(): SeoSitemap
    {
        $urls = [
            [
                'loc' => route('locations.index'),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
        ];
        $this->syncUrlRecord(route('locations.index'), 'directory', 'Browse Adult Personals by Location', 'Explore adult dating and personal classifieds by state and city.');

        // High-intent City + Category combinations for locations with active listings
        $activeListingPairs = Listing::where('status', Listing::STATUS_ACTIVE)
            ->whereNotNull('state_id')
            ->whereNotNull('city_id')
            ->whereNotNull('category_id')
            ->with(['state', 'city', 'category'])
            ->get();

        $seenCityCat = [];
        foreach ($activeListingPairs as $item) {
            if (! $item->state || ! $item->city || ! $item->category) {
                continue;
            }
            $stateCode = strtolower($item->state->code);
            $citySlug = $item->city->slug;
            $catSlug = $item->category->slug;
            $key = "{$stateCode}/{$citySlug}/{$catSlug}";

            if (isset($seenCityCat[$key])) {
                continue;
            }
            $seenCityCat[$key] = true;

            $cityCatUrl = route('locations.city.category', [
                'stateCode' => $stateCode,
                'citySlug' => $citySlug,
                'categorySlug' => $catSlug,
            ]);

            $urls[] = [
                'loc' => $cityCatUrl,
                'lastmod' => $item->updated_at ? $item->updated_at->toAtomString() : now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.7',
            ];

            $this->syncUrlRecord(
                $cityCatUrl,
                'location_city_category',
                "{$item->category->name} in {$item->city->name}, {$item->state->code}",
                "Browse local {$item->category->name} in {$item->city->name}, {$item->state->name}.",
                $item
            );
        }

        $xml = $this->buildUrlsetXml($urls);
        return $this->recordSitemap('locations-sitemap.xml', $xml, count($urls));
    }

    /**
     * Dedicated US States Sitemap (Phase 4).
     */
    public function generateStatesSitemap(): SeoSitemap
    {
        $urls = [];
        $states = State::where('is_active', true)->get();

        foreach ($states as $st) {
            $url = route('locations.state', ['stateCode' => strtolower($st->code)]);
            $urls[] = [
                'loc' => $url,
                'lastmod' => $st->updated_at->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ];
        }

        $xml = $this->buildUrlsetXml($urls);
        return $this->recordSitemap('states-sitemap.xml', $xml, count($urls));
    }

    /**
     * Dedicated US Cities Sitemap (Phase 4).
     */
    public function generateCitiesSitemap(): SeoSitemap
    {
        $urls = [];
        $cities = City::where('is_active', true)->with('state')->limit(5000)->get();

        foreach ($cities as $ct) {
            if ($ct->state) {
                $url = route('locations.city', ['stateCode' => strtolower($ct->state->code), 'citySlug' => $ct->slug]);
                $urls[] = [
                    'loc' => $url,
                    'lastmod' => $ct->updated_at->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.6',
                ];
            }
        }

        $xml = $this->buildUrlsetXml($urls);
        return $this->recordSitemap('cities-sitemap.xml', $xml, count($urls));
    }

    /**
     * Active Listings Sitemap (Excludes expired, unpublished, deleted).
     */
    public function generateListingsSitemap(): SeoSitemap
    {
        $urls = [];
        $listings = Listing::where('status', Listing::STATUS_ACTIVE)
            ->whereNotNull('published_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('published_at')
            ->limit(25000)
            ->get();

        foreach ($listings as $l) {
            $url = route('listings.show', $l->slug);
            $urls[] = [
                'loc' => $url,
                'lastmod' => ($l->renewed_at ?: $l->updated_at)->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => $l->is_featured ? '0.9' : '0.7',
            ];

            $this->syncUrlRecord(
                $url,
                'listing',
                $l->title,
                \Illuminate\Support\Str::limit(strip_tags($l->description), 155),
                $l
            );
        }

        $xml = $this->buildUrlsetXml($urls);
        return $this->recordSitemap('listings-sitemap.xml', $xml, count($urls));
    }

    /**
     * Images Sitemap (Phase 4 & Phase 11).
     */
    public function generateImagesSitemap(): SeoSitemap
    {
        $listings = Listing::where('status', Listing::STATUS_ACTIVE)
            ->with(['photos'])
            ->latest('published_at')
            ->limit(5000)
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        $count = 0;
        foreach ($listings as $l) {
            if ($l->photos->isEmpty()) {
                continue;
            }

            $url = route('listings.show', $l->slug);
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>\n";

            foreach ($l->photos->take(5) as $photo) {
                $imgPath = $photo->url ?? asset('storage/' . $photo->file_path);
                $title = htmlspecialchars($photo->original_filename ?: $l->title, ENT_XML1);
                $xml .= "    <image:image>\n";
                $xml .= "      <image:loc>" . htmlspecialchars($imgPath, ENT_XML1) . "</image:loc>\n";
                $xml .= "      <image:title>" . $title . "</image:title>\n";
                $xml .= "    </image:image>\n";
                $count++;
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $this->recordSitemap('images-sitemap.xml', $xml, $count);
    }

    /**
     * Build standard urlset XML.
     */
    public function buildUrlsetXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            if (! empty($u['lastmod'])) {
                $xml .= "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
            }
            if (! empty($u['changefreq'])) {
                $xml .= "    <changefreq>" . $u['changefreq'] . "</changefreq>\n";
            }
            if (! empty($u['priority'])) {
                $xml .= "    <priority>" . $u['priority'] . "</priority>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Record sitemap in public storage and database registry.
     */
    protected function recordSitemap(string $name, string $xml, int $urlCount): SeoSitemap
    {
        // Validate XML
        $isValid = true;
        $validationError = null;

        libxml_use_internal_errors(true);
        $parsed = simplexml_load_string($xml);
        if ($parsed === false) {
            $isValid = false;
            $errors = libxml_get_errors();
            $validationError = ! empty($errors) ? $errors[0]->message : 'Malformed XML syntax';
            libxml_clear_errors();
        }

        // Store to public disk so web server can serve quickly
        $filePath = 'sitemaps/' . $name;
        Storage::disk('public')->put($filePath, $xml);

        $size = strlen($xml);

        return SeoSitemap::updateOrCreate(
            ['name' => $name],
            [
                'file_path' => $filePath,
                'url_count' => $urlCount,
                'file_size_bytes' => $size,
                'is_valid_xml' => $isValid,
                'validation_error' => $validationError,
                'last_generated_at' => now(),
                'submission_status' => 'submitted',
                'last_submitted_at' => now(),
            ]
        );
    }

    /**
     * Synchronize URL into seo_url_records inventory.
     */
    public function syncUrlRecord(string $url, string $type, ?string $title, ?string $description, ?object $model = null): SeoUrlRecord
    {
        return SeoUrlRecord::updateOrCreate(
            ['url' => $url],
            [
                'url_type' => $type,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->id ?? null,
                'canonical_url' => $url,
                'indexing_status' => 'discovered',
                'indexing_eligibility' => 'eligible',
                'sitemap_status' => 'included',
                'http_status' => 200,
                'meta_title' => $title,
                'meta_description' => $description,
                'h1' => $title,
                'seo_health_score' => 100,
            ]
        );
    }
}

