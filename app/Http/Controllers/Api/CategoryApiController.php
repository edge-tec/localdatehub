<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryApiController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Retrieve complete hierarchical category tree.
     */
    public function tree(): JsonResponse
    {
        $categories = $this->categoryService->getActiveCategoryTree();

        $formatCategory = function (Category $cat) use (&$formatCategory) {
            return [
                'id' => $cat->id,
                'parent_id' => $cat->parent_id,
                'depth' => $cat->depth,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'code' => $cat->code,
                'description' => $cat->description,
                'safety_guidelines' => $cat->safety_guidelines,
                'icon' => $cat->icon,
                'min_age_override' => $cat->min_age_override,
                'display_order' => $cat->display_order,
                'is_featured' => $cat->is_featured,
                'listings_count' => $cat->listings_count,
                'subcategories' => $cat->allActiveChildren->map($formatCategory)->values(),
            ];
        };

        return response()->json([
            'status' => 'success',
            'data' => $categories->map($formatCategory)->values(),
        ]);
    }

    /**
     * Retrieve category details by slug or ID.
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->orWhere('id', is_numeric($slug) ? $slug : 0)
            ->with(['parent', 'activeChildren'])
            ->first();

        if (!$category) {
            return response()->json(['status' => 'error', 'message' => 'Category not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'depth' => $category->depth,
                'name' => $category->name,
                'slug' => $category->slug,
                'code' => $category->code,
                'description' => $category->description,
                'safety_guidelines' => $category->safety_guidelines,
                'icon' => $category->icon,
                'min_age_override' => $category->min_age_override,
                'is_active' => $category->is_active,
                'is_featured' => $category->is_featured,
                'breadcrumbs' => $category->getBreadcrumbs(),
                'subcategories' => $category->activeChildren->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'code' => $c->code,
                    'listings_count' => $c->listings()->where('status', 'active')->count(),
                ]),
            ],
        ]);
    }

    /**
     * Retrieve dynamic attributes and options for listing submission and filter forms.
     */
    public function attributes(Category $category): JsonResponse
    {
        $attributes = $category->allAttributes();

        return response()->json([
            'status' => 'success',
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'attributes' => $attributes->map(fn ($attr) => [
                'id' => $attr->id,
                'category_id' => $attr->category_id,
                'is_inherited' => $attr->category_id !== $category->id,
                'name' => $attr->name,
                'code' => $attr->code,
                'field_type' => $attr->field_type,
                'placeholder' => $attr->placeholder,
                'help_text' => $attr->help_text,
                'unit' => $attr->unit,
                'is_required' => $attr->is_required,
                'is_searchable' => $attr->is_searchable,
                'is_filterable' => $attr->is_filterable,
                'display_order' => $attr->display_order,
                'validation_rules' => $attr->getValidationRuleList(),
                'options' => $attr->options->map(fn ($opt) => [
                    'id' => $opt->id,
                    'label' => $opt->label,
                    'value' => $opt->value,
                ]),
            ]),
        ]);
    }
}

