<?php

namespace App\Http\Controllers\SEO;

use App\Http\Controllers\Controller;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\SEO\Models\CmsPage;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Master Sitemap Index.
     */
    public function index(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        $subSitemaps = [
            route('sitemaps.pages'),
            route('sitemaps.categories'),
            route('sitemaps.locations'),
            route('sitemaps.listings'),
        ];

        foreach ($subSitemaps as $url) {
            $xml .= '<sitemap>';
            $xml .= '<loc>' . htmlspecialchars($url, ENT_XML1) . '</loc>';
            $xml .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
            $xml .= '</sitemap>';
        }

        $xml .= '</sitemapindex>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * Static & CMS Pages Sitemap.
     */
    public function pages(): Response
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'hourly'],
            ['loc' => url('/search'), 'priority' => '0.9', 'changefreq' => 'hourly'],
            ['loc' => url('/dmca'), 'priority' => '0.5', 'changefreq' => 'monthly'],
        ];

        $cms = CmsPage::where('is_published', true)->get();
        foreach ($cms as $page) {
            $urls[] = [
                'loc' => url('/' . $page->slug),
                'priority' => '0.6',
                'changefreq' => 'weekly',
            ];
        }

        return $this->buildUrlsetResponse($urls);
    }

    /**
     * Category Pages Sitemap.
     */
    public function categories(): Response
    {
        $urls = [];
        $categories = Category::where('is_active', true)->get();
        foreach ($categories as $cat) {
            $urls[] = [
                'loc' => route('categories.show', $cat->slug),
                'priority' => '0.8',
                'changefreq' => 'daily',
            ];
        }

        return $this->buildUrlsetResponse($urls);
    }

    /**
     * State & City Locations Sitemap.
     */
    public function locations(): Response
    {
        $urls = [];
        $states = State::where('is_active', true)->with('cities')->get();
        foreach ($states as $st) {
            $urls[] = [
                'loc' => route('locations.state', ['stateCode' => strtolower($st->code)]),
                'priority' => '0.7',
                'changefreq' => 'daily',
            ];

            foreach ($st->cities->take(50) as $ct) {
                $urls[] = [
                    'loc' => route('locations.city', ['stateCode' => strtolower($st->code), 'citySlug' => $ct->slug]),
                    'priority' => '0.6',
                    'changefreq' => 'daily',
                ];
            }
        }

        return $this->buildUrlsetResponse($urls);
    }

    /**
     * Active Listings Sitemap.
     */
    public function listings(): Response
    {
        $urls = [];
        $listings = Listing::where('status', Listing::STATUS_ACTIVE)
            ->latest('published_at')
            ->limit(2000)
            ->get();

        foreach ($listings as $l) {
            $urls[] = [
                'loc' => route('listings.show', $l->slug),
                'lastmod' => ($l->renewed_at ?: $l->updated_at)->toAtomString(),
                'priority' => $l->is_featured ? '0.9' : '0.7',
                'changefreq' => 'weekly',
            ];
        }

        return $this->buildUrlsetResponse($urls);
    }

    /**
     * Dynamic robots.txt
     */
    public function robots(): Response
    {
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

    protected function buildUrlsetResponse(array $urls): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $u) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>';
            if (isset($u['lastmod'])) {
                $xml .= '<lastmod>' . $u['lastmod'] . '</lastmod>';
            }
            if (isset($u['changefreq'])) {
                $xml .= '<changefreq>' . $u['changefreq'] . '</changefreq>';
            }
            if (isset($u['priority'])) {
                $xml .= '<priority>' . $u['priority'] . '</priority>';
            }
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}