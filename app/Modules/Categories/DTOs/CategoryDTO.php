<?php

namespace App\Modules\Categories\DTOs;

use App\Modules\Categories\Models\Category;

readonly class CategoryDTO
{
    /**
     * @param array<string, mixed>|null $seoMetadata
     * @param array<string>|null $allowedContactMethods
     * @param array<CategoryDTO> $children
     */
    public function __construct(
        public string $id,
        public ?int $parentId,
        public int $depth,
        public string $name,
        public string $slug,
        public ?string $code,
        public string $description,
        public ?string $safetyGuidelines,
        public string $icon,
        public int $displayOrder = 0,
        public bool $isActive = true,
        public bool $isFeatured = false,
        public ?int $minAgeOverride = null,
        public ?array $allowedContactMethods = null,
        public ?array $seoMetadata = null,
        public array $children = [],
        public int $listingsCount = 0,
    ) {}

    public static function fromModel(Category $category, bool $includeChildren = true): self
    {
        $children = [];
        if ($includeChildren && $category->relationLoaded('children')) {
            $children = $category->children
                ->map(fn (Category $child) => self::fromModel($child, $includeChildren))
                ->all();
        }

        return new self(
            id: (string) $category->id,
            parentId: $category->parent_id,
            depth: $category->depth,
            name: $category->name,
            slug: $category->slug,
            code: $category->code,
            description: $category->description ?? '',
            safetyGuidelines: $category->safety_guidelines,
            icon: $category->icon ?? 'heart',
            displayOrder: (int) $category->display_order,
            isActive: (bool) $category->is_active,
            isFeatured: (bool) $category->is_featured,
            minAgeOverride: $category->min_age_override,
            allowedContactMethods: $category->allowed_contact_methods,
            seoMetadata: $category->seo_metadata,
            children: $children,
            listingsCount: (int) ($category->listings_count ?? 0),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            depth: (int) ($data['depth'] ?? 0),
            name: $data['name'],
            slug: $data['slug'],
            code: $data['code'] ?? null,
            description: $data['description'] ?? '',
            safetyGuidelines: $data['safety_guidelines'] ?? null,
            icon: $data['icon'] ?? 'heart',
            displayOrder: (int) ($data['display_order'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true),
            isFeatured: (bool) ($data['is_featured'] ?? false),
            minAgeOverride: isset($data['min_age_override']) ? (int) $data['min_age_override'] : null,
            allowedContactMethods: $data['allowed_contact_methods'] ?? null,
            seoMetadata: $data['seo_metadata'] ?? null,
            children: $data['children'] ?? [],
            listingsCount: (int) ($data['listings_count'] ?? 0),
        );
    }
}
