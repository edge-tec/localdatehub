<?php

namespace App\Modules\Locations\DTOs;

readonly class CoordinateDTO
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public ?string $cityName = null,
        public ?string $state = null,
        public ?string $country = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            cityName: $data['city_name'] ?? $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
        );
    }
}
