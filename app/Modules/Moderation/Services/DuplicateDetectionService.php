<?php

namespace App\Modules\Moderation\Services;

use App\Modules\Listings\Models\Listing;

class DuplicateDetectionService
{
    /**
     * Normalize content for hash matching (strip whitespace, punctuation, lower case).
     */
    public function generateContentHash(string $title, string $description): string
    {
        $cleaned = preg_replace('/[^a-z0-9]/', '', strtolower($title . ' ' . $description));

        return hash('sha256', (string) $cleaned);
    }

    /**
     * Check if a listing has duplicate matches across the system.
     *
     * @return array{is_duplicate: bool, match_listing_id: ?int, similarity: float}
     */
    public function check(string $title, string $description, ?int $ignoreListingId = null): array
    {
        $hash = $this->generateContentHash($title, $description);

        // 1. Exact normalized hash match
        $query = Listing::where('content_hash', $hash);
        if ($ignoreListingId) {
            $query->where('id', '!=', $ignoreListingId);
        }
        $exact = $query->first();

        if ($exact) {
            return [
                'is_duplicate' => true,
                'match_listing_id' => $exact->id,
                'similarity' => 100.0,
            ];
        }

        // 2. Near-duplicate similarity check against recent listings (last 14 days)
        $recent = Listing::where('created_at', '>=', now()->subDays(14))
            ->when($ignoreListingId, fn ($q) => $q->where('id', '!=', $ignoreListingId))
            ->latest()
            ->limit(30)
            ->get(['id', 'title', 'description']);

        $needle = strtolower(substr($description, 0, 300));

        foreach ($recent as $listing) {
            $haystack = strtolower(substr((string) $listing->description, 0, 300));
            similar_text($needle, $haystack, $percent);

            if ($percent >= 85.0) {
                return [
                    'is_duplicate' => true,
                    'match_listing_id' => $listing->id,
                    'similarity' => round($percent, 2),
                ];
            }
        }

        return [
            'is_duplicate' => false,
            'match_listing_id' => null,
            'similarity' => 0.0,
        ];
    }
}
