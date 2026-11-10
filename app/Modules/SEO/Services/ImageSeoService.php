<?php

namespace App\Modules\SEO\Services;

use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingMedia;
use Illuminate\Support\Str;

class ImageSeoService
{
    /**
     * Generate an optimized ALT text for a listing media item.
     */
    public function generateAltText(ListingMedia $photo): string
    {
        $listing = $photo->listing;
        if (! $listing) {
            return $photo->original_filename ?: 'LocalDateHub Classified Photo';
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
     * Auto-optimize missing metadata across all active listing photos.
     */
    public function optimizeMissingPhotoAlts(): int
    {
        $media = ListingMedia::whereNull('original_filename')
            ->orWhere('original_filename', '')
            ->with(['listing.category', 'listing.city', 'listing.state'])
            ->limit(200)
            ->get();

        $updated = 0;
        foreach ($media as $item) {
            $alt = $this->generateAltText($item);
            $item->update(['original_filename' => $alt]);
            $updated++;
        }

        return $updated;
    }
}
