<?php

namespace App\Modules\SEO\Services;

use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingPhoto;
use Illuminate\Support\Str;

class ImageSeoService
{
    /**
     * Generate an optimized ALT text for a listing photo.
     */
    public function generateAltText(ListingPhoto $photo): string
    {
        if (! empty(trim($photo->caption ?? ''))) {
            return trim($photo->caption);
        }

        $listing = $photo->listing;
        if (! $listing) {
            return 'LocalDateHub Classified Photo';
        }

        $parts = [];
        $parts[] = $listing->title;

        if ($listing->category) {
            $parts[] = $listing->category->name;
        }

        if ($listing->city && $listing->state) {
            $parts[] = "in {$listing->city->name}, {$listing->state->code}";
        } elseif ($listing->state) {
            $parts[] = "in {$listing->state->name}";
        }

        return Str::limit(implode(' — ', $parts), 120);
    }

    /**
     * Auto-fill missing alt captions across all active listing photos.
     */
    public function optimizeMissingPhotoAlts(): int
    {
        $photos = ListingPhoto::whereNull('caption')
            ->orWhere('caption', '')
            ->with(['listing.category', 'listing.city', 'listing.state'])
            ->limit(200)
            ->get();

        $updated = 0;
        foreach ($photos as $photo) {
            $alt = $this->generateAltText($photo);
            $photo->update(['caption' => $alt]);
            $updated++;
        }

        return $updated;
    }
}

