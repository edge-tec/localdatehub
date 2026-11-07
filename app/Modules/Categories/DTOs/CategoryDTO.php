<?php

namespace App\Modules\Categories\DTOs;

readonly class CategoryDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $description,
        public string $icon,
        public int $displayOrder = 0,
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? '',
            icon: $data['icon'] ?? 'heart',
            displayOrder: (int) ($data['display_order'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }
}
