<?php

namespace App\Modules\SEO\DTOs;

readonly class MetaTagsDTO
{
    public function __construct(
        public string $title,
        public string $description,
        public ?string $canonicalUrl = null,
        public ?string $ogImage = null,
        public string $ogType = 'website',
        public string $robots = 'index, follow',
        public ?string $city = null,
        public ?string $state = null,
        public ?string $category = null,
        public array $structuredData = [],
        public array $breadcrumbs = [],
        public ?string $prevPageUrl = null,
        public ?string $nextPageUrl = null
    ) {}

    public function toJsonLd(): string
    {
        if (empty($this->structuredData)) {
            return '';
        }

        return json_encode($this->structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}