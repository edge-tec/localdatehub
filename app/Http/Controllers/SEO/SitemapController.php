<?php

namespace App\Http\Controllers\SEO;

use App\Http\Controllers\Controller;
use App\Modules\SEO\Models\SeoSitemap;
use App\Modules\SEO\Services\SitemapService;
use App\Modules\Admin\Models\SystemSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class SitemapController extends Controller
{
    public function __construct(
        protected SitemapService $sitemapService
    ) {}

    /**
     * Master Sitemap Index: /sitemap.xml or /sitemap_index.xml
     */
    public function index(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $subSitemaps = [
            route('sitemaps.pages'),
            route('sitemaps.categories'),
            route('sitemaps.locations'),
            route('sitemaps.states'),
            route('sitemaps.cities'),
            route('sitemaps.listings'),
            route('sitemaps.images'),
        ];

        foreach ($subSitemaps as $url) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>" . now()->toAtomString() . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * Pages & CMS Sitemap: /sitemaps/pages.xml
     */
    public function pages(): Response
    {
        $sitemap = SeoSitemap::where('name', 'pages-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return response(Storage::disk('public')->get($sitemap->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        }

        $record = $this->sitemapService->generatePagesSitemap();
        return response(Storage::disk('public')->get($record->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * Category Pages Sitemap: /sitemaps/categories.xml
     */
    public function categories(): Response
    {
        $sitemap = SeoSitemap::where('name', 'categories-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return response(Storage::disk('public')->get($sitemap->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        }

        $record = $this->sitemapService->generateCategoriesSitemap();
        return response(Storage::disk('public')->get($record->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * State & City Locations Sitemap: /sitemaps/locations.xml
     */
    public function locations(): Response
    {
        $sitemap = SeoSitemap::where('name', 'locations-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return response(Storage::disk('public')->get($sitemap->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        }

        $record = $this->sitemapService->generateLocationsSitemap();
        return response(Storage::disk('public')->get($record->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * Dedicated US States Sitemap: /sitemaps/states.xml
     */
    public function states(): Response
    {
        $sitemap = SeoSitemap::where('name', 'states-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return response(Storage::disk('public')->get($sitemap->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        }

        $record = $this->sitemapService->generateStatesSitemap();
        return response(Storage::disk('public')->get($record->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * Dedicated US Cities Sitemap: /sitemaps/cities.xml
     */
    public function cities(): Response
    {
        $sitemap = SeoSitemap::where('name', 'cities-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return response(Storage::disk('public')->get($sitemap->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        }

        $record = $this->sitemapService->generateCitiesSitemap();
        return response(Storage::disk('public')->get($record->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * Active Listings Sitemap: /sitemaps/listings.xml
     */
    public function listings(): Response
    {
        $sitemap = SeoSitemap::where('name', 'listings-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return response(Storage::disk('public')->get($sitemap->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        }

        $record = $this->sitemapService->generateListingsSitemap();
        return response(Storage::disk('public')->get($record->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * Images Sitemap: /sitemaps/images.xml
     */
    public function images(): Response
    {
        $sitemap = SeoSitemap::where('name', 'images-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return response(Storage::disk('public')->get($sitemap->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        }

        $record = $this->sitemapService->generateImagesSitemap();
        return response(Storage::disk('public')->get($record->file_path), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * Dynamic robots.txt with admin-editable custom directives.
     */
    public function robots(): Response
    {
        $customRobots = null;
        try {
            $setting = SystemSetting::where('group', 'seo')->where('key', 'robots_txt')->first();
            $customRobots = $setting?->value;
        } catch (\Throwable $e) {
            // fallback
        }

        if (! empty(trim($customRobots))) {
            return response($customRobots, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /account/\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /api/\n";
        $content .= "Disallow: /webhooks/\n";
        $content .= "Disallow: /listings/create\n";
        $content .= "Disallow: /*/edit\n";
        $content .= "\n";
        $content .= "Sitemap: " . route('sitemap.index') . "\n";

        return response($content, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
