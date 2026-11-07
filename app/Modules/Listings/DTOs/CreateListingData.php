<?php

namespace App\Modules\Listings\DTOs;

readonly class CreateListingData
{
    public function __construct(
        public int $userId,
        public string $title,
        public string $content,
        public string $categorySlug,
        public string $cityName,
        public float $latitude,
        public float $longitude,
        public int $age,
        public ?string $intent = 'dating',
        public array $photos = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            title: trim($data['title']),
            content: trim($data['content']),
            categorySlug: $data['category_slug'],
            cityName: trim($data['city_name']),
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            age: (int) $data['age'],
            intent: $data['intent'] ?? 'dating',
            photos: $data['photos'] ?? [],
        );
    }
}
