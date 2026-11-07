<?php

namespace App\Modules\SEO\DTOs;

readonly class MetaTagsDTO
{
    public function __construct(
        public string $title,
        public string $description,
        public ?string $canonicalUrl = null,
        public ?string $ogImage = null,
        public string $robots = 'index, follow',
        public ?string $city = null,
    ) {}
}
