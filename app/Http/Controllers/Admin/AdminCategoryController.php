<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Models\CategoryAttribute;
use App\Modules\Categories\Models\CategoryAttributeOption;
use App\Modules\Categories\Services\CategoryManagementService;
use App\Modules\Categories\Services\CategoryService;
use App\Modules\Listings\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService,
        protected CategoryManagementService $categoryManagementService
    ) {}

    /**
     * Authorize administrator access.
     */
    protected function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'super_admin'])) {
            abort(403, 'Unauthorized administrative access.');
        }
    }

    /**
     * List all categories hierarchically with stats and controls.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $rootCategories = Category::whereNull('parent_id')
            ->with(['allChildren' => function ($q) {
                $q->withCount('listings');
            }])
            ->withCount('listings')
            ->orderBy('display_order')
            ->get();

        $totalCategories = Category::count();
        $activeCategories = Category::where('is_active', true)->count();
        $totalListings = Listing::count();

        return view('admin.categories.index', [
            'rootCategories' => $rootCategories,
            'totalCategories' => $totalCategories,
            'activeCategories' => $activeCategories,
            'totalListings' => $totalListings,
        ]);
    }

    /**
     * Show form to create a new category.
     */
    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        $parentOptions = $this->categoryService->getCategoryDropdownList(true);
        $selectedParentId = $request->input('parent_id');

        return view('admin.categories.create', [
            'parentOptions' => $parentOptions,
            'selectedParentId' => $selectedParentId,
        ]);
    }

    /**
     * Store newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:120',
            'code' => 'nullable|string|max:20',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string|max:2000',
            'safety_guidelines' => 'nullable|string|max:3000',
            'icon' => 'nullable|string|max:50',
            'min_age_override' => 'nullable|integer|min:18|max:99',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'allowed_contact_methods' => 'nullable|array',
            'meta_title' => 'nullable|string|max:150',
            'meta_description' => 'nullable|string|max:300',
        ]);

        $data = $validated;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured', false);

        if (!empty($validated['meta_title']) || !empty($validated['meta_description'])) {
            $data['seo_metadata'] = [
                'meta_title' => $validated['meta_title'] ?? $validated['name'],
                'meta_description' => $validated['meta_description'] ?? '',
            ];
        }

        $category = $this->categoryManagementService->createCategory($data);

        return redirect()->route('admin.categories.index')
            ->with('status', "Category '{$category->name}' created successfully.");
    }

    /**
     * Show edit form for a category.
     */
    public function edit(Category $category, Request $request): View
    {
        $this->authorizeAdmin($request);

        $parentOptions = $this->categoryService->getCategoryDropdownList(true);
        // Exclude self and descendants from parent options
        $excludeIds = $category->descendantIds(true);
        $parentOptions = array_filter($parentOptions, fn ($opt) => !in_array($opt['id'], $excludeIds, true));

        return view('admin.categories.edit', [
            'category' => $category,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * Update category.
     */
    public function update(Category $category, Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:120',
            'code' => 'nullable|string|max:20',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string|max:2000',
            'safety_guidelines' => 'nullable|string|max:3000',
            'icon' => 'nullable|string|max:50',
            'min_age_override' => 'nullable|integer|min:18|max:99',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'allowed_contact_methods' => 'nullable|array',
            'meta_title' => 'nullable|string|max:150',
            'meta_description' => 'nullable|string|max:300',
        ]);

        $data = $validated;
        $data['is_active'] = $request->boolean('is_active', false);
        $data['is_featured'] = $request->boolean('is_featured', false);

        if (!empty($validated['meta_title']) || !empty($validated['meta_description'])) {
            $data['seo_metadata'] = [
                'meta_title' => $validated['meta_title'] ?? $validated['name'],
                'meta_description' => $validated['meta_description'] ?? '',
            ];
        }

        $this->categoryManagementService->updateCategory($category, $data);

        return redirect()->route('admin.categories.index')
            ->with('status', "Category '{$category->name}' updated successfully.");
    }

    /**
     * Admin preview of a category (even when inactive).
     */
    public function preview(Category $category, Request $request): View
    {
        $this->authorizeAdmin($request);

        $listings = Listing::where('category_id', $category->id)
            ->with(['city', 'state', 'primaryPhoto'])
            ->latest()
            ->paginate(15);

        $subcategories = $category->children()->withCount('listings')->get();
        $attributes = $category->allAttributes();

        return view('categories.show', [
            'category' => $category,
            'subcategories' => $subcategories,
            'attributes' => $attributes,
            'listings' => $listings,
            'breadcrumbs' => $category->getBreadcrumbs(),
            'isPreview' => true,
        ]);
    }

    /**
     * Show reassignment form.
     */
    public function reassign(Category $category, Request $request): View
    {
        $this->authorizeAdmin($request);

        $listingsCount = Listing::where('category_id', $category->id)->count();
        $targetOptions = Category::where('id', '!=', $category->id)->orderBy('name')->get();

        return view('admin.categories.reassign', [
            'category' => $category,
            'listingsCount' => $listingsCount,
            'targetOptions' => $targetOptions,
        ]);
    }

    /**
     * Execute reassignment of listings.
     */
    public function doReassign(Category $category, Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $request->validate([
            'target_category_id' => 'required|integer|exists:categories,id|different:category_id',
        ]);

        $target = Category::findOrFail($request->input('target_category_id'));
        $count = $this->categoryManagementService->reassignListings($category, $target, $request->user()?->id);

        return redirect()->route('admin.categories.index')
            ->with('status', "Successfully migrated {$count} listings from '{$category->name}' to '{$target->name}'.");
    }

    /**
     * Delete a category with dependency checks.
     */
    public function destroy(Category $category, Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $check = $this->categoryManagementService->canDeleteCategory($category);

        if (!$check['can_delete']) {
            if ($request->filled('reassign_to_category_id')) {
                $targetId = (int) $request->input('reassign_to_category_id');
                $this->categoryManagementService->deleteCategory($category, $targetId, $request->user()?->id);
                return redirect()->route('admin.categories.index')
                    ->with('status', "Category '{$category->name}' deleted and listings reassigned.");
            }

            return redirect()->route('admin.categories.reassign', $category->id)
                ->with('error', $check['reason']);
        }

        $name = $category->name;
        $this->categoryManagementService->deleteCategory($category);

        return redirect()->route('admin.categories.index')
            ->with('status', "Category '{$name}' deleted successfully.");
    }

    /**
     * Manage category attributes.
     */
    public function attributes(Category $category, Request $request): View
    {
        $this->authorizeAdmin($request);

        $attributes = $category->attributes()->with('options')->get();
        $inheritedAttributes = $category->ancestors()->map(fn ($a) => $a->attributes()->with('options')->get())->flatten();
        $globalAttributes = CategoryAttribute::whereNull('category_id')->with('options')->get();

        return view('admin.categories.attributes', [
            'category' => $category,
            'attributes' => $attributes,
            'inheritedAttributes' => $inheritedAttributes,
            'globalAttributes' => $globalAttributes,
        ]);
    }

    /**
     * Store new attribute for a category.
     */
    public function storeAttribute(Category $category, Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50|alpha_dash',
            'field_type' => 'required|string|in:' . implode(',', CategoryAttribute::FIELD_TYPES),
            'placeholder' => 'nullable|string|max:150',
            'help_text' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:30',
            'is_required' => 'nullable|boolean',
            'is_searchable' => 'nullable|boolean',
            'is_filterable' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $attr = CategoryAttribute::create([
            'category_id' => $category->id,
            'name' => $validated['name'],
            'code' => strtolower($validated['code']),
            'field_type' => $validated['field_type'],
            'placeholder' => $validated['placeholder'] ?? null,
            'help_text' => $validated['help_text'] ?? null,
            'unit' => $validated['unit'] ?? null,
            'is_required' => $request->boolean('is_required', false),
            'is_searchable' => $request->boolean('is_searchable', true),
            'is_filterable' => $request->boolean('is_filterable', true),
            'display_order' => $validated['display_order'] ?? 10,
        ]);

        return redirect()->route('admin.categories.attributes', $category->id)
            ->with('status', "Attribute '{$attr->name}' added successfully.");
    }

    /**
     * Delete category attribute.
     */
    public function deleteAttribute(CategoryAttribute $attribute, Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $categoryId = $attribute->category_id;
        $name = $attribute->name;
        $attribute->delete();

        return redirect()->route('admin.categories.attributes', $categoryId ?: 1)
            ->with('status', "Attribute '{$name}' deleted successfully.");
    }

    /**
     * Store option for a select/radio attribute.
     */
    public function storeAttributeOption(CategoryAttribute $attribute, Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'value' => 'required|string|max:100|alpha_dash',
            'display_order' => 'nullable|integer|min:0',
        ]);

        CategoryAttributeOption::create([
            'category_attribute_id' => $attribute->id,
            'label' => $validated['label'],
            'value' => strtolower($validated['value']),
            'display_order' => $validated['display_order'] ?? 1,
        ]);

        return back()->with('status', "Option '{$validated['label']}' added.");
    }

    /**
     * Delete an attribute option.
     */
    public function deleteAttributeOption(CategoryAttributeOption $option, Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $label = $option->label;
        $option->delete();

        return back()->with('status', "Option '{$label}' removed.");
    }
}

