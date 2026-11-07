<?php

namespace App\Modules\Search\DTOs;

readonly class SearchFilterDTO
{
    public function __construct(
        public ?string $query = null,
        public ?string $categorySlug = null,
        public ?string $cityName = null,
        public ?string $stateCode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public int $radiusKm = 25,
        public int $minAge = 18,
        public int $maxAge = 99,
        public bool $verifiedOnly = false,
        public bool $withPhotosOnly = false,
        public string $sort = 'latest',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            query: !empty($data['q']) ? trim($data['q']) : null,
            categorySlug: !empty($data['category']) ? trim($data['category']) : null,
            cityName: !empty($data['city']) ? trim($data['city']) : null,
            stateCode: !empty($data['state']) ? strtoupper(trim($data['state'])) : null,
            latitude: isset($data['lat']) && is_numeric($data['lat']) ? (float) $data['lat'] : null,
            longitude: isset($data['lng']) && is_numeric($data['lng']) ? (float) $data['lng'] : null,
            radiusKm: min(max((int) ($data['radius'] ?? config('localdatehub.geolocation.default_radius_km', 25)), 1), config('localdatehub.geolocation.max_radius_km', 150)),
            minAge: max((int) ($data['min_age'] ?? config('localdatehub.safety.min_age', 18)), config('localdatehub.safety.min_age', 18)),
            maxAge: !empty($data['max_age']) ? min(max((int) $data['max_age'], 18), 99) : 99,
            verifiedOnly: filter_var($data['verified_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
            withPhotosOnly: filter_var($data['with_photos'] ?? false, FILTER_VALIDATE_BOOLEAN),
            sort: in_array($data['sort'] ?? '', ['latest', 'views', 'oldest'], true) ? $data['sort'] : 'latest',
        );
    }
}
