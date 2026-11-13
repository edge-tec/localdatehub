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

        return $this->xmlResponse($xml, 3600);
    }

    /**
     * Pages & CMS Sitemap: /sitemaps/pages.xml
     */
    public function pages(): Response
    {
        $sitemap = SeoSitemap::where('name', 'pages-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return $this->xmlResponse(Storage::disk('public')->get($sitemap->file_path), 3600);
        }

        $record = $this->sitemapService->generatePagesSitemap();
        return $this->xmlResponse(Storage::disk('public')->get($record->file_path), 3600);
    }

    /**
     * Category Pages Sitemap: /sitemaps/categories.xml
     */
    public function categories(): Response
    {
        $sitemap = SeoSitemap::where('name', 'categories-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return $this->xmlResponse(Storage::disk('public')->get($sitemap->file_path), 3600);
        }

        $record = $this->sitemapService->generateCategoriesSitemap();
        return $this->xmlResponse(Storage::disk('public')->get($record->file_path), 3600);
    }

    /**
     * State & City Locations Sitemap: /sitemaps/locations.xml
     */
    public function locations(): Response
    {
        $sitemap = SeoSitemap::where('name', 'locations-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return $this->xmlResponse(Storage::disk('public')->get($sitemap->file_path), 7200);
        }

        $record = $this->sitemapService->generateLocationsSitemap();
        return $this->xmlResponse(Storage::disk('public')->get($record->file_path), 7200);
    }

    /**
     * Dedicated US States Sitemap: /sitemaps/states.xml
     */
    public function states(): Response
    {
        $sitemap = SeoSitemap::where('name', 'states-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return $this->xmlResponse(Storage::disk('public')->get($sitemap->file_path), 86400);
        }

        $record = $this->sitemapService->generateStatesSitemap();
        return $this->xmlResponse(Storage::disk('public')->get($record->file_path), 86400);
    }

    /**
     * Dedicated US Cities Sitemap: /sitemaps/cities.xml
     */
    public function cities(): Response
    {
        $sitemap = SeoSitemap::where('name', 'cities-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return $this->xmlResponse(Storage::disk('public')->get($sitemap->file_path), 7200);
        }

        $record = $this->sitemapService->generateCitiesSitemap();
        return $this->xmlResponse(Storage::disk('public')->get($record->file_path), 7200);
    }

    /**
     * Active Listings Sitemap: /sitemaps/listings.xml
     */
    public function listings(): Response
    {
        $sitemap = SeoSitemap::where('name', 'listings-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return $this->xmlResponse(Storage::disk('public')->get($sitemap->file_path), 1800);
        }

        $record = $this->sitemapService->generateListingsSitemap();
        return $this->xmlResponse(Storage::disk('public')->get($record->file_path), 1800);
    }

    /**
     * Images Sitemap: /sitemaps/images.xml
     */
    public function images(): Response
    {
        $sitemap = SeoSitemap::where('name', 'images-sitemap.xml')->first();
        if ($sitemap && $sitemap->file_path && Storage::disk('public')->exists($sitemap->file_path)) {
            return $this->xmlResponse(Storage::disk('public')->get($sitemap->file_path), 3600);
        }

        $record = $this->sitemapService->generateImagesSitemap();
        return $this->xmlResponse(Storage::disk('public')->get($record->file_path), 3600);
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
            return $this->textResponse($customRobots, 86400);
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

        return $this->textResponse($content, 86400);
    }

    /**
     * Diagnostic endpoint for SEO subsystem.
     */
    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'subsystem' => 'Enterprise SEO Management',
            'timestamp' => now()->toIso8601String(),
            'sitemaps' => [
                'index' => route('sitemap.index'),
                'pages' => route('sitemaps.pages'),
                'categories' => route('sitemaps.categories'),
                'locations' => route('sitemaps.locations'),
                'states' => route('sitemaps.states'),
                'cities' => route('sitemaps.cities'),
                'listings' => route('sitemaps.listings'),
                'images' => route('sitemaps.images'),
            ],
            'robots' => route('seo.robots'),
        ], 200, [
            'Cache-Control' => 'public, max-age=60',
        ]);
    }

    /**
     * Exact Google Search Console HTML File Verification (e.g. /google1234567890abcdef.html)
     * Validates against configured Google verification token to prevent arbitrary 200 responses.
     */
    public function googleVerification(string $code): Response
    {
        $configuredToken = null;
        try {
            $configuredToken = SystemSetting::where('group', 'seo')
                ->where('key', 'google_verification_code')
                ->value('value') ?: config('services.google.verification_code');
        } catch (\Throwable $e) {
            // DB not ready fallback
        }

        if (empty($configuredToken) || $code !== $configuredToken) {
            abort(404, 'Google Site Verification token is not configured or did not match.');
        }

        return response("google-site-verification: google{$configuredToken}.html", 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Google Search Console & Webmaster status endpoint.
     */
    public function googleStatus()
    {
        $gsc = app(\App\Modules\SEO\Services\GoogleSearchConsoleService::class);
        return response()->json([
            'service' => 'Google Search Console & Webmaster Integration',
            'settings' => $gsc->getSettings(),
            'timestamp' => now()->toIso8601String(),
        ], 200, [
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Google URL Inspection API query endpoint.
     */
    public function googleInspection(\Illuminate\Http\Request $request)
    {
        $url = (string) $request->input('url', $request->query('url', ''));
        if (empty($url)) {
            return response()->json(['error' => 'A valid URL is required for inspection.'], 422);
        }

        $gsc = app(\App\Modules\SEO\Services\GoogleSearchConsoleService::class);
        $result = $gsc->inspectUrl($url);

        return response()->json($result);
    }

    /**
     * Trigger Google Search Console analytics synchronization.
     */
    public function googleSync()
    {
        $gsc = app(\App\Modules\SEO\Services\GoogleSearchConsoleService::class);
        $result = $gsc->syncAnalytics();

        return response()->json($result);
    }

    /**
     * Inbound Google Indexing & Webmaster notification webhook.
     */
    public function googleIndexingWebhook(\Illuminate\Http\Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Inbound Google Webmaster/Indexing Webhook received', [
            'payload' => $request->only(['url', 'type', 'event', 'notifyTime']),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ]);

        return response()->json(['status' => 'acknowledged', 'received_at' => now()->toIso8601String()]);
    }

    /**
     * Return XML response with optimal public cache-control and no session cookies.
     */
    protected function xmlResponse(string $xml, int $maxAge = 3600): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=' . $maxAge . ', s-maxage=' . $maxAge . ', stale-while-revalidate=600',
        ]);
    }

    /**
     * Return Text response with optimal public cache-control.
     */
    protected function textResponse(string $text, int $maxAge = 86400): Response
    {
        return response($text, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=' . $maxAge . ', s-maxage=' . $maxAge . ', stale-while-revalidate=3600',
        ]);
    }
}
