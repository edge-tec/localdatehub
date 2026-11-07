<?php

namespace App\Modules\Categories\Services;

use App\Modules\Categories\DTOs\CategoryDTO;
use Illuminate\Support\Collection;

class CategoryService
{
    /**
     * Default classified and dating categories adhering strictly to safety guidelines.
     */
    protected array $categories = [
        [
            'id' => 'dating-men-seeking-women',
            'name' => 'Men Seeking Women',
            'slug' => 'men-seeking-women',
            'description' => 'Local adult dating and romantic connections.',
            'icon' => 'user-heart',
            'display_order' => 1,
            'is_active' => true,
        ],
        [
            'id' => 'dating-women-seeking-men',
            'name' => 'Women Seeking Men',
            'slug' => 'women-seeking-men',
            'description' => 'Local adult dating and romantic connections.',
            'icon' => 'user-heart',
            'display_order' => 2,
            'is_active' => true,
        ],
        [
            'id' => 'dating-same-gender',
            'name' => 'LGBTQ+ Connections',
            'slug' => 'lgbtq-connections',
            'description' => 'Local adult LGBTQ+ dating and social discovery.',
            'icon' => 'rainbow',
            'display_order' => 3,
            'is_active' => true,
        ],
        [
            'id' => 'activity-partners',
            'name' => 'Activity & Date Companions',
            'slug' => 'activity-companions',
            'description' => 'Find a date partner for concerts, dining, travel, or fitness.',
            'icon' => 'calendar',
            'display_order' => 4,
            'is_active' => true,
        ],
        [
            'id' => 'serious-relationships',
            'name' => 'Serious & Long-Term',
            'slug' => 'serious-relationships',
            'description' => 'Intentional dating focused on committed relationships.',
            'icon' => 'sparkles',
            'display_order' => 5,
            'is_active' => true,
        ],
        [
            'id' => 'casual-coffee-dates',
            'name' => 'Casual & Coffee Meets',
            'slug' => 'casual-coffee-meets',
            'description' => 'Low-pressure local coffee dates and social mixers.',
            'icon' => 'coffee',
            'display_order' => 6,
            'is_active' => true,
        ],
    ];

    /**
     * Retrieve all active categories.
     *
     * @return Collection<int, CategoryDTO>
     */
    public function getActiveCategories(): Collection
    {
        return collect($this->categories)
            ->filter(fn ($cat) => $cat['is_active'])
            ->sortBy('display_order')
            ->map(fn ($cat) => CategoryDTO::fromArray($cat))
            ->values();
    }

    /**
     * Find category by slug.
     */
    public function findBySlug(string $slug): ?CategoryDTO
    {
        $category = collect($this->categories)->firstWhere('slug', $slug);
        return $category ? CategoryDTO::fromArray($category) : null;
    }
}
