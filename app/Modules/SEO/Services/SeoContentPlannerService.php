<?php

namespace App\Modules\SEO\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\SEO\Models\SeoContentSuggestion;

class SeoContentPlannerService
{
    /**
     * Generate content and FAQ suggestions based on active categories and states.
     */
    public function generateAutomatedSuggestions(): int
    {
        $categories = Category::where('is_active', true)->get();
        $count = 0;

        foreach ($categories as $cat) {
            $existing = SeoContentSuggestion::where('target_type', 'category')
                ->where('target_id', $cat->id)
                ->first();

            if (! $existing) {
                SeoContentSuggestion::create([
                    'topic' => "Safety & Best Practices for {$cat->name}",
                    'target_type' => 'category',
                    'target_id' => $cat->id,
                    'suggested_title' => "Complete Safety Guide: How to Connect Safely in {$cat->name}",
                    'suggested_keywords' => [
                        "safe {$cat->name} dating",
                        "verified {$cat->name} ads",
                        "how to avoid scams in {$cat->name}",
                    ],
                    'search_intent' => 'informational',
                    'faq_suggestions' => [
                        [
                            'q' => "How do I verify advertisers in {$cat->name}?",
                            'a' => "Look for verified badges on the listing card and communicate initially through the platform.",
                        ],
                        [
                            'q' => "Is posting in {$cat->name} free?",
                            'a' => "Yes, basic classified ads are free, with optional priority boosts available.",
                        ],
                    ],
                    'internal_link_targets' => [
                        route('categories.show', $cat->slug),
                        url('/safety'),
                    ],
                    'status' => 'planned',
                ]);
                $count++;
            }
        }

        return $count;
    }
}

