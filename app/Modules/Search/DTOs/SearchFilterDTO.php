<?php

namespace App\Modules\Search\DTOs;

readonly class SearchFilterDTO
{
    public function __construct(
        public ?string $query = null,
        public ?string $categorySlug = null,
        public ?string $cityName = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public int $radiusKm = 25,
        public int $minAge = 18,
        public int $maxAge = 99,
        public bool $verifiedOnly = false,
        public bool $withPhotosOnly = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            query: !empty($data['q']) ? trim($data['q']) : null,
            categorySlug: $data['category'] ?? null,
            cityName: $data['city'] ?? null,
            latitude: isset($data['lat']) ? (float) $data['lat'] : null,
            longitude: isset($data['lng']) ? (float) $data['lng'] : null,
            radiusKm: min((int) ($data['radius'] ?? config('localdatehub.geolocation.default_radius_km', 25)), config('localdatehub.geolocation.max_radius_km', 150)),
            minAge: max((int) ($data['min_age'] ?? config('localdatehub.safety.min_age', 18)), config('localdatehub.safety.min_age', 18)),
            maxAge: (int) ($data['max_age'] ?? 99),
            verifiedOnly: (bool) ($data['verified_only'] ?? false),
            withPhotosOnly: (bool) ($data['with_photos'] ?? false),
        );
    }
}
