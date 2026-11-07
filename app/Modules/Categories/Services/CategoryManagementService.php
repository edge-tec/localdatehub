<?php

namespace App\Modules\Categories\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class CategoryManagementService
{
    /**
     * Create a new category.
     *
     * @param array<string, mixed> $data
     * @return Category
     */
    public function createCategory(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $name = trim($data['name']);
            $slug = !empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($name);

            // Ensure slug uniqueness
            $slug = $this->generateUniqueSlug($slug);

            $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
            $depth = 0;

            if ($parentId) {
                $parent = Category::findOrFail($parentId);
                $depth = $parent->depth + 1;
                if ($depth > Category::MAX_DEPTH) {
                    throw new InvalidArgumentException(
                        "Cannot create category: maximum depth of " . Category::MAX_DEPTH . " exceeded."
                    );
                }
            }

            $displayOrder = $data['display_order'] ?? $this->getNextDisplayOrder($parentId);

            return Category::create([
                'parent_id' => $parentId,
                'depth' => $depth,
                'name' => $name,
                'slug' => $slug,
                'code' => !empty($data['code']) ? strtoupper(trim($data['code'])) : null,
                'description' => $data['description'] ?? null,
                'safety_guidelines' => $data['safety_guidelines'] ?? null,
                'icon' => $data['icon'] ?? 'heart',
                'min_age_override' => !empty($data['min_age_override']) ? (int) $data['min_age_override'] : null,
                'display_order' => $displayOrder,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'allowed_contact_methods' => $data['allowed_contact_methods'] ?? ['platform_message', 'email_relay', 'phone'],
                'seo_metadata' => $data['seo_metadata'] ?? [
                    'meta_title' => "{$name} Classifieds & Connections | LocalAdZone",
                    'meta_description' => "Browse verified adult {$name} listings in your local area.",
                ],
            ]);
        });
    }

    /**
     * Update an existing category.
     *
     * @param Category $category
     * @param array<string, mixed> $data
     * @return Category
     */
    public function updateCategory(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            if (isset($data['name'])) {
                $category->name = trim($data['name']);
            }

            if (isset($data['slug']) && $data['slug'] !== $category->slug) {
                $category->slug = $this->generateUniqueSlug(Str::slug($data['slug']), $category->id);
            }

            if (array_key_exists('parent_id', $data)) {
                $newParentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

                if ($newParentId === $category->id) {
                    throw new InvalidArgumentException('A category cannot be its own parent.');
                }

                // Check that new parent is not a descendant of this category (prevent cycle)
                if ($newParentId) {
                    $descendantIds = $category->descendantIds(false);
                    if (in_array($newParentId, $descendantIds, true)) {
                        throw new InvalidArgumentException('Cannot set a descendant category as parent.');
                    }

                    $newParent = Category::findOrFail($newParentId);
                    $newDepth = $newParent->depth + 1;
                    if ($newDepth > Category::MAX_DEPTH) {
                        throw new InvalidArgumentException("Maximum category depth of " . Category::MAX_DEPTH . " exceeded.");
                    }
                    $category->parent_id = $newParentId;
                    $category->depth = $newDepth;
                } else {
                    $category->parent_id = null;
                    $category->depth = 0;
                }
            }

            if (array_key_exists('code', $data)) {
                $category->code = !empty($data['code']) ? strtoupper(trim($data['code'])) : null;
            }

            if (array_key_exists('description', $data)) {
                $category->description = $data['description'];
            }

            if (array_key_exists('safety_guidelines', $data)) {
                $category->safety_guidelines = $data['safety_guidelines'];
            }

            if (array_key_exists('icon', $data)) {
                $category->icon = $data['icon'] ?: 'heart';
            }

            if (array_key_exists('min_age_override', $data)) {
                $category->min_age_override = !empty($data['min_age_override']) ? (int) $data['min_age_override'] : null;
            }

            if (array_key_exists('display_order', $data)) {
                $category->display_order = (int) $data['display_order'];
            }

            if (array_key_exists('is_active', $data)) {
                $category->is_active = (bool) $data['is_active'];
            }

            if (array_key_exists('is_featured', $data)) {
                $category->is_featured = (bool) $data['is_featured'];
            }

            if (array_key_exists('allowed_contact_methods', $data)) {
                $category->allowed_contact_methods = $data['allowed_contact_methods'];
            }

            if (array_key_exists('seo_metadata', $data)) {
                $category->seo_metadata = $data['seo_metadata'];
            }

            $category->save();

            // Refresh depths of all descendants if parent changed
            if ($category->wasChanged('depth')) {
                $this->refreshDescendantDepths($category);
            }

            return $category;
        });
    }

    /**
     * Recursively update descendant depths.
     */
    protected function refreshDescendantDepths(Category $parent): void
    {
        foreach ($parent->children as $child) {
            $child->depth = $parent->depth + 1;
            if ($child->depth > Category::MAX_DEPTH) {
                throw new InvalidArgumentException("Hierarchy exceeds maximum depth of " . Category::MAX_DEPTH . ".");
            }
            $child->saveQuietly();
            $this->refreshDescendantDepths($child);
        }
    }

    /**
     * Reassign all listings from source category to target category.
     *
     * @param Category $source
     * @param Category $target
     * @param int|null $changedByUserId
     * @return int Number of listings migrated
     */
    public function reassignListings(Category $source, Category $target, ?int $changedByUserId = null): int
    {
        if ($source->id === $target->id) {
            throw new InvalidArgumentException('Source and target categories must be different.');
        }

        return DB::transaction(function () use ($source, $target, $changedByUserId) {
            $listings = Listing::where('category_id', $source->id)->get();
            $count = $listings->count();

            if ($count === 0) {
                return 0;
            }

            // Update listings category_id
            Listing::where('category_id', $source->id)->update([
                'category_id' => $target->id,
                'updated_at' => now(),
            ]);

            // Record status history for audit trail
            foreach ($listings as $listing) {
                ListingStatusHistory::create([
                    'listing_id' => $listing->id,
                    'from_status' => $listing->status,
                    'to_status' => $listing->status,
                    'reason' => "Category migrated from '{$source->name}' to '{$target->name}' by admin.",
                    'changed_by_user_id' => $changedByUserId,
                ]);
            }

            return $count;
        });
    }

    /**
     * Check if a category can be safely deleted.
     *
     * @param Category $category
     * @return array{can_delete: bool, reason: string|null, listings_count: int, children_count: int}
     */
    public function canDeleteCategory(Category $category): array
    {
        $listingsCount = Listing::where('category_id', $category->id)->count();
        $childrenCount = $category->children()->count();

        if ($listingsCount > 0) {
            return [
                'can_delete' => false,
                'reason' => "Category has {$listingsCount} active listings. Please reassign listings before deleting.",
                'listings_count' => $listingsCount,
                'children_count' => $childrenCount,
            ];
        }

        if ($childrenCount > 0) {
            return [
                'can_delete' => false,
                'reason' => "Category has {$childrenCount} subcategories. Please reassign or delete subcategories first.",
                'listings_count' => $listingsCount,
                'children_count' => $childrenCount,
            ];
        }

        return [
            'can_delete' => true,
            'reason' => null,
            'listings_count' => 0,
            'children_count' => 0,
        ];
    }

    /**
     * Safely delete a category with optional listings reassignment.
     *
     * @param Category $category
     * @param int|null $reassignToCategoryId
     * @param int|null $changedByUserId
     * @return bool
     */
    public function deleteCategory(Category $category, ?int $reassignToCategoryId = null, ?int $changedByUserId = null): bool
    {
        return DB::transaction(function () use ($category, $reassignToCategoryId, $changedByUserId) {
            $listingsCount = Listing::where('category_id', $category->id)->count();

            if ($listingsCount > 0) {
                if (!$reassignToCategoryId) {
                    throw new RuntimeException("Cannot delete category with {$listingsCount} listings without specifying a reassignment target.");
                }

                $targetCategory = Category::findOrFail($reassignToCategoryId);
                $this->reassignListings($category, $targetCategory, $changedByUserId);
            }

            // Check children
            if ($category->children()->count() > 0) {
                if (!$reassignToCategoryId) {
                    throw new RuntimeException("Cannot delete category with active subcategories without a parent reassignment target.");
                }
                // Reassign children to category's parent or target category
                $targetParentId = $category->parent_id ?: $reassignToCategoryId;
                $category->children()->update([
                    'parent_id' => $targetParentId,
                    'depth' => DB::raw('depth - 1'),
                ]);
            }

            return (bool) $category->delete();
        });
    }

    /**
     * Batch update display orders.
     *
     * @param array<int, int> $orderMap [category_id => order]
     */
    public function updateDisplayOrders(array $orderMap): void
    {
        DB::transaction(function () use ($orderMap) {
            foreach ($orderMap as $categoryId => $order) {
                Category::where('id', $categoryId)->update(['display_order' => (int) $order]);
            }
        });
    }

    /**
     * Generate unique slug by appending incremental numeric suffix if taken.
     */
    protected function generateUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $original = $slug;
        $counter = 1;

        while (Category::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Get next display order for child within a parent.
     */
    protected function getNextDisplayOrder(?int $parentId): int
    {
        $max = Category::where('parent_id', $parentId)->max('display_order');
        return ($max !== null) ? $max + 1 : 1;
    }
}

