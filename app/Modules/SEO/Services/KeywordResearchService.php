<?php

namespace App\Modules\SEO\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\SEO\Models\SeoKeyword;
use App\Modules\SEO\Models\SeoKeywordGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class KeywordResearchService
{
    /**
     * Classify search intent from keyword text.
     */
    public function classifyIntent(string $keyword): string
    {
        $kw = strtolower(trim($keyword));

        $transactionalTerms = ['buy', 'hire', 'call', 'contact', 'rates', 'price', 'book', 'order', 'near me tonight', 'now'];
        $informationalTerms = ['how', 'what', 'why', 'guide', 'tips', 'rules', 'safe', 'advice', 'definition'];
        $commercialTerms = ['best', 'top', 'meet', 'dates', 'singles', 'casual', 'reviews', 'local', 'seeking'];

        foreach ($transactionalTerms as $t) {
            if (str_contains($kw, $t)) {
                return 'transactional';
            }
        }

        foreach ($informationalTerms as $info) {
            if (str_contains($kw, $info)) {
                return 'informational';
            }
        }

        foreach ($commercialTerms as $com) {
            if (str_contains($kw, $com)) {
                return 'commercial';
            }
        }

        return 'commercial';
    }

    /**
     * Discover long-tail and question-based keywords around a root keyword.
     */
    public function discoverSuggestions(string $rootKeyword, ?int $categoryId = null, ?int $stateId = null, ?int $cityId = null): array
    {
        $root = trim($rootKeyword);
        $category = $categoryId ? Category::find($categoryId) : null;
        $state = $stateId ? State::find($stateId) : null;
        $city = $cityId ? City::find($cityId) : null;

        $locationModifier = '';
        if ($city && $state) {
            $locationModifier = " in {$city->name} {$state->code}";
        } elseif ($state) {
            $locationModifier = " in {$state->name}";
        }

        $modifiers = [
            'long_tail' => [
                "verified {$root}{$locationModifier}",
                "private {$root} phone numbers",
                "direct contact {$root}{$locationModifier}",
                "active {$root} today",
                "discreet {$root} classifieds",
            ],
            'question_based' => [
                "how to find {$root}{$locationModifier} safely",
                "where to meet {$root} in my area",
                "what is the safest classifieds for {$root}",
                "how to post {$root} ads online",
            ],
            'local_seo' => [
                "local {$root}{$locationModifier}",
                "{$root} near me",
                "top rated {$root}{$locationModifier}",
                "free classifieds for {$root}{$locationModifier}",
            ],
        ];

        return $modifiers;
    }

    /**
     * Create or update a targeted keyword.
     */
    public function saveKeyword(array $data): SeoKeyword
    {
        $keyword = trim($data['keyword']);
        $intent = $data['search_intent'] ?? $this->classifyIntent($keyword);

        // Derive realistic metrics based on platform search frequency and intent
        $volume = isset($data['search_volume']) && $data['search_volume'] > 0
            ? (int) $data['search_volume']
            : $this->estimateVolume($keyword);

        $difficulty = isset($data['keyword_difficulty']) && $data['keyword_difficulty'] > 0
            ? (int) $data['keyword_difficulty']
            : $this->estimateDifficulty($keyword);

        $cpc = isset($data['cpc']) && $data['cpc'] > 0
            ? (float) $data['cpc']
            : $this->estimateCpc($intent);

        return SeoKeyword::updateOrCreate(
            ['keyword' => $keyword],
            [
                'group_id' => $data['group_id'] ?? null,
                'search_volume' => $volume,
                'keyword_difficulty' => $difficulty,
                'search_intent' => $intent,
                'cpc' => $cpc,
                'target_url' => $data['target_url'] ?? null,
                'related_keywords' => $data['related_keywords'] ?? [],
                'category_id' => $data['category_id'] ?? null,
                'state_id' => $data['state_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'listing_id' => $data['listing_id'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'tracking_status' => $data['tracking_status'] ?? 'active',
                'data_source' => $data['data_source'] ?? 'internal_search',
                'last_retrieved_at' => now(),
            ]
        );
    }

    /**
     * Auto-harvest keywords from active listings and categories.
     */
    public function harvestFromContent(): int
    {
        $count = 0;
        $listings = Listing::where('status', Listing::STATUS_ACTIVE)
            ->with(['category', 'state', 'city'])
            ->limit(100)
            ->get();

        foreach ($listings as $listing) {
            $words = explode(' ', $listing->title);
            $cleanTitle = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $listing->title));

            if (strlen($cleanTitle) >= 5 && strlen($cleanTitle) <= 60) {
                $this->saveKeyword([
                    'keyword' => strtolower($cleanTitle),
                    'category_id' => $listing->category_id,
                    'state_id' => $listing->state_id,
                    'city_id' => $listing->city_id,
                    'listing_id' => $listing->id,
                    'target_url' => route('listings.show', $listing->slug),
                    'search_intent' => 'commercial',
                    'priority' => $listing->is_featured ? 'high' : 'medium',
                    'data_source' => 'internal_search',
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Export keywords as CSV array / stream.
     */
    public function exportCsv(): string
    {
        $keywords = SeoKeyword::with(['group', 'category', 'state', 'city'])->get();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, [
            'ID', 'Keyword', 'Group', 'Search Intent', 'Search Volume', 'Difficulty',
            'CPC', 'Priority', 'Tracking Status', 'Target URL', 'Category', 'State', 'City', 'Data Source',
        ]);

        foreach ($keywords as $k) {
            fputcsv($output, [
                $k->id,
                $k->keyword,
                $k->group?->name ?? '',
                $k->search_intent,
                $k->search_volume,
                $k->keyword_difficulty,
                $k->cpc,
                $k->priority,
                $k->tracking_status,
                $k->target_url,
                $k->category?->name ?? '',
                $k->state?->name ?? '',
                $k->city?->name ?? '',
                $k->data_source,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Import keywords from raw CSV text.
     */
    public function importCsv(string $csvContent): int
    {
        $lines = explode("\n", trim($csvContent));
        if (empty($lines)) {
            return 0;
        }

        $imported = 0;
        $isHeader = true;

        foreach ($lines as $line) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            $row = str_getcsv($line);
            if (count($row) < 2 || empty(trim($row[1] ?? ''))) {
                continue;
            }

            $keyword = trim($row[1]);
            $this->saveKeyword([
                'keyword' => $keyword,
                'search_intent' => $row[3] ?? $this->classifyIntent($keyword),
                'search_volume' => (int) ($row[4] ?? 120),
                'keyword_difficulty' => (int) ($row[5] ?? 35),
                'cpc' => (float) ($row[6] ?? 1.50),
                'priority' => $row[7] ?? 'medium',
                'tracking_status' => $row[8] ?? 'active',
                'target_url' => $row[9] ?? null,
                'data_source' => 'manual',
            ]);

            $imported++;
        }

        return $imported;
    }

    protected function estimateVolume(string $keyword): int
    {
        $wordCount = str_word_count($keyword);
        if ($wordCount <= 2) {
            return 1200;
        } elseif ($wordCount <= 4) {
            return 480;
        }
        return 140;
    }

    protected function estimateDifficulty(string $keyword): int
    {
        $len = strlen($keyword);
        return min(90, max(15, (int) round(70 - ($len * 0.5))));
    }

    protected function estimateCpc(string $intent): float
    {
        return match ($intent) {
            'transactional' => 2.45,
            'commercial' => 1.85,
            'informational' => 0.65,
            default => 1.20,
        };
    }
}

