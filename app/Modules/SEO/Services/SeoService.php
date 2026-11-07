<?php

namespace App\Modules\SEO\Services;

use App\Modules\SEO\DTOs\MetaTagsDTO;

class SeoService
{
    /**
     * Generate metadata for local landing and category pages.
     */
    public function generateForCityAndCategory(?string $city = null, ?string $categoryName = null): MetaTagsDTO
    {
        $baseName = config('localdatehub.name', 'LocalDateHub');

        if ($city && $categoryName) {
            $title = "{$categoryName} in {$city} | {$baseName}";
            $description = "Connect with local singles for {$categoryName} in {$city}. Verified adult personal connections, privacy-safe proximity discovery, and date spots.";
        } elseif ($city) {
            $title = "Local Dating & Personals in {$city} | {$baseName}";
            $description = "Meet verified adult singles in {$city}. Browse local profiles, chat safely, and discover romantic date ideas in {$city}.";
        } elseif ($categoryName) {
            $title = "{$categoryName} Personals | {$baseName}";
            $description = "Browse verified {$categoryName} local dating ads, personal connections, and activity partners.";
        } else {
            $title = "{$baseName} — Private Local Dating & Personal Connections";
            $description = "The premier privacy-first adult dating and personal classifieds hub. Real local connections, verified members, and curated date ideas.";
        }

        return new MetaTagsDTO(
            title: $title,
            description: $description,
            canonicalUrl: url()->current(),
            ogImage: asset('images/og-localdatehub.jpg'),
            robots: 'index, follow',
            city: $city
        );
    }
}
