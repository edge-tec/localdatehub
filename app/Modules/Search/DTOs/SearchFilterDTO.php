<?php

namespace App\Modules\Search\DTOs;

readonly class SearchFilterDTO
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public ?string $query = null,
        public ?string $categorySlug = null,
        public ?string $subcategorySlug = null,
        public ?string $stateCode = null,
        public ?string $cityName = null,
        public ?string $zipCode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public int $radiusKm = 25,
        public int $minAge = 18,
        public int $maxAge = 99,
        public bool $verifiedOnly = false,
        public bool $withPhotosOnly = false,
        public bool $featuredOnly = false,
        public string $publishedDate = 'all', // all, today, 7d, 30d
        public array $attributes = [],
        public string $sort = 'newest', // newest, oldest, updated, views
        public int $page = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        $rawAttributes = $data['attr'] ?? [];
        $cleanAttributes = [];
        if (is_array($rawAttributes)) {
            foreach ($rawAttributes as $k => $v) {
                if ($v !== null && $v !== '') {
                    $cleanAttributes[trim((string) $k)] = is_string($v) ? trim($v) : $v;
                }
            }
        }

        $sort = strtolower(trim((string) ($data['sort'] ?? 'newest')));
        if ($sort === 'latest') {
            $sort = 'newest';
        }
        if (!in_array($sort, ['newest', 'oldest', 'updated', 'views'], true)) {
            $sort = 'newest';
        }

        $publishedDate = strtolower(trim((string) ($data['published_date'] ?? 'all')));
        if (!in_array($publishedDate, ['all', 'today', '7d', '30d'], true)) {
            $publishedDate = 'all';
        }

        $zip = !empty($data['zip']) ? preg_replace('/[^0-9]/', '', (string) $data['zip']) : null;
        if ($zip && strlen($zip) > 5) {
            $zip = substr($zip, 0, 5);
        }

        return new self(
            query: !empty($data['q']) ? trim((string) $data['q']) : null,
            categorySlug: !empty($data['category']) ? trim((string) $data['category']) : null,
            subcategorySlug: !empty($data['subcategory']) ? trim((string) $data['subcategory']) : null,
            stateCode: !empty($data['state']) ? strtoupper(trim((string) $data['state'])) : null,
            cityName: !empty($data['city']) ? trim((string) $data['city']) : null,
            zipCode: !empty($zip) ? $zip : null,
            latitude: isset($data['lat']) && is_numeric($data['lat']) ? (float) $data['lat'] : null,
            longitude: isset($data['lng']) && is_numeric($data['lng']) ? (float) $data['lng'] : null,
            radiusKm: min(max((int) ($data['radius'] ?? config('localdatehub.geolocation.default_radius_km', 25)), 1), config('localdatehub.geolocation.max_radius_km', 150)),
            minAge: max((int) ($data['min_age'] ?? config('localdatehub.safety.min_age', 18)), config('localdatehub.safety.min_age', 18)),
            maxAge: !empty($data['max_age']) ? min(max((int) $data['max_age'], 18), 99) : 99,
            verifiedOnly: filter_var($data['verified_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
            withPhotosOnly: filter_var($data['with_photos'] ?? false, FILTER_VALIDATE_BOOLEAN),
            featuredOnly: filter_var($data['featured_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
            publishedDate: $publishedDate,
            attributes: $cleanAttributes,
            sort: $sort,
            page: max(1, (int) ($data['page'] ?? 1)),
        );
    }

    /**
     * Check if any location-specific criterion is provided.
     */
    public function hasLocation(): bool
    {
        return !empty($this->stateCode) ||
               !empty($this->cityName) ||
               !empty($this->zipCode) ||
               ($this->latitude !== null && $this->longitude !== null);
    }

    /**
     * Export all non-empty filter parameters for preserving state in links & forms.
     */
    public function toQueryArray(): array
    {
        $params = [];
        if (!empty($this->query)) $params['q'] = $this->query;
        if (!empty($this->categorySlug)) $params['category'] = $this->categorySlug;
        if (!empty($this->subcategorySlug)) $params['subcategory'] = $this->subcategorySlug;
        if (!empty($this->stateCode)) $params['state'] = $this->stateCode;
        if (!empty($this->cityName)) $params['city'] = $this->cityName;
        if (!empty($this->zipCode)) $params['zip'] = $this->zipCode;
        if ($this->latitude !== null) $params['lat'] = $this->latitude;
        if ($this->longitude !== null) $params['lng'] = $this->longitude;
        if ($this->radiusKm !== config('localdatehub.geolocation.default_radius_km', 25)) $params['radius'] = $this->radiusKm;
        if ($this->minAge > 18) $params['min_age'] = $this->minAge;
        if ($this->maxAge < 99) $params['max_age'] = $this->maxAge;
        if ($this->verifiedOnly) $params['verified_only'] = 1;
        if ($this->withPhotosOnly) $params['with_photos'] = 1;
        if ($this->featuredOnly) $params['featured_only'] = 1;
        if ($this->publishedDate !== 'all') $params['published_date'] = $this->publishedDate;
        if (!empty($this->attributes)) $params['attr'] = $this->attributes;
        if ($this->sort !== 'newest') $params['sort'] = $this->sort;

        return $params;
    }
}
