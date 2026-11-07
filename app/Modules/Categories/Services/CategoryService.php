<?php

namespace App\Modules\Categories\Services;

use App\Modules\Categories\DTOs\CategoryDTO;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Models\CategoryAttribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    /**
     * Retrieve all active leaf categories or subcategories for form selects and browsing.
     *
     * @return Collection<int, CategoryDTO>
     */
    public function getActiveCategories(): Collection
    {
        if (!Schema::hasTable('categories')) {
            return collect();
        }

        return Category::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(fn (Category $cat) => CategoryDTO::fromModel($cat, false));
    }

    /**
     * Retrieve complete active hierarchical tree (Root -> Groups -> Subcategories).
     *
     * @return Collection<int, Category>
     */
    public function getActiveCategoryTree(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['allActiveChildren' => function ($q) {
                $q->withCount('listings');
            }])
            ->withCount('listings')
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Retrieve category groups directly under Personals root (depth 1).
     *
     * @return Collection<int, Category>
     */
    public function getCategoryGroups(): Collection
    {
        $root = Category::whereNull('parent_id')->first();
        if (!$root) {
            return collect();
        }

        return Category::query()
            ->where('parent_id', $root->id)
            ->where('is_active', true)
            ->with(['activeChildren' => function ($q) {
                $q->withCount('listings');
            }])
            ->withCount('listings')
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Find category by slug.
     */
    public function findBySlug(string $slug, bool $includeInactive = false): ?Category
    {
        $query = Category::query()->where('slug', $slug);

        if (!$includeInactive) {
            $query->where('is_active', true);
        }

        return $query->with(['parent', 'attributes.options'])->first();
    }

    /**
     * Find category by slug and return as DTO (backwards compatible).
     */
    public function findDtoBySlug(string $slug): ?CategoryDTO
    {
        $cat = $this->findBySlug($slug);
        return $cat ? CategoryDTO::fromModel($cat) : null;
    }

    /**
     * Get flat list of categories with indentation for administrative and dropdown selection.
     *
     * @return array<int, array{id: int, name: string, formatted_name: string, depth: int, slug: string}>
     */
    public function getCategoryDropdownList(bool $includeInactive = true): array
    {
        $categories = Category::query()
            ->when(!$includeInactive, fn ($q) => $q->where('is_active', true))
            ->orderBy('depth')
            ->orderBy('display_order')
            ->get();

        $result = [];
        $buildTree = function ($parentId, $prefix = '') use (&$buildTree, &$result, $categories) {
            $children = $categories->where('parent_id', $parentId)->sortBy('display_order');
            foreach ($children as $child) {
                $result[] = [
                    'id' => $child->id,
                    'name' => $child->name,
                    'formatted_name' => $prefix . ($prefix ? '— ' : '') . $child->name,
                    'depth' => $child->depth,
                    'slug' => $child->slug,
                    'is_active' => $child->is_active,
                ];
                $buildTree($child->id, $prefix . '    ');
            }
        };

        $buildTree(null);

        return $result;
    }

    /**
     * Validate dynamic listing attributes against category rules.
     *
     * @param Category $category
     * @param array<string, mixed> $inputAttributes
     * @return array<string, mixed> Validated attribute data
     * @throws ValidationException
     */
    public function validateListingAttributes(Category $category, array $inputAttributes): array
    {
        $attributes = $category->allAttributes();
        $rules = [];
        $customAttributes = [];

        foreach ($attributes as $attr) {
            $key = $attr->code;
            $attrRules = $attr->getValidationRuleList();
            $rules[$key] = $attrRules;
            $customAttributes[$key] = $attr->name;
        }

        $validator = Validator::make($inputAttributes, $rules, [], $customAttributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
