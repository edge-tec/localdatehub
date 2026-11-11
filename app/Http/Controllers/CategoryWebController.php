<?php

namespace App\Http\Controllers;

use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Services\CategoryService;
use App\Modules\Listings\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryWebController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * National Category Directory page showing full hierarchy of Personals and listing counts.
     */
    public function index(): View
    {
        $categories = $this->categoryService->getActiveCategoryTree();

        $totalListings = Listing::where('status', 'active')->count();

        return view('categories.index', [
            'rootCategories' => $categories,
            'totalListings' => $totalListings,
        ]);
    }

    /**
     * Category Landing Page showing child subcategories, dynamic filters, recent listings, and safety notice.
     */
    public function show(string $slug, Request $request): View
    {
        $category = Category::where('slug', $slug)
            ->with(['parent', 'attributes.options'])
            ->first();

        if (!$category) {
            abort(404, "Category [{$slug}] not found.");
        }

        $isAdmin = $request->user() && in_array($request->user()->role, ['admin', 'super_admin']);
        $isPreview = $request->boolean('preview', false) || $request->routeIs('admin.categories.preview');

        // Non-admins cannot see inactive categories
        if (!$category->is_active && !($isAdmin && $isPreview)) {
            abort(404, "Category is not currently active.");
        }

        // Fetch descendant category IDs to show all relevant listings (e.g. all listings in 'Men Seeking')
        $categoryIds = $category->descendantIds(true);

        $listingsQuery = Listing::whereIn('category_id', $categoryIds)
            ->where('status', 'active')
            ->with(['city', 'state', 'primaryPhoto'])
            ->latest('published_at');

        // Apply attribute filters if provided
        if ($request->has('attr') && is_array($request->input('attr'))) {
            foreach ($request->input('attr') as $attrCode => $val) {
                if (!empty($val)) {
                    $listingsQuery->whereHas('attributes', function ($q) use ($attrCode, $val) {
                        $q->where('attribute_code', $attrCode)
                          ->where(function ($sq) use ($val) {
                              $sq->where('value_text', $val)
                                 ->orWhere('value_number', is_numeric($val) ? $val : null);
                          });
                    });
                }
            }
        }

        $listings = $listingsQuery->paginate(15)->withQueryString();

        // Direct active subcategories
        $subcategories = $category->children()
            ->when(!$isAdmin || !$isPreview, fn ($q) => $q->where('is_active', true))
            ->withCount(['listings' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('display_order')
            ->get();

        // All applicable dynamic attributes
        $attributes = $category->allAttributes()->where('is_filterable', true);

        $seo = app(\App\Modules\SEO\Services\SeoService::class)->generateForCategory($category, (int) $request->input('page', 1));

        $adContext = [
            'category_id' => $category->id,
            'parent_category_id' => $category->parent_id,
        ];
        $categoryFeedItems = app(\App\Modules\Advertising\Services\AdDeliveryService::class)
            ->injectIntoListings($listings->getCollection(), \App\Modules\Advertising\Models\AdCampaign::SLOT_CATEGORY_PAGE, $adContext);

        return view('categories.show', [
            'category' => $category,
            'subcategories' => $subcategories,
            'attributes' => $attributes,
            'listings' => $listings,
            'categoryFeedItems' => $categoryFeedItems,
            'adContext' => $adContext,
            'breadcrumbs' => $category->getBreadcrumbs(),
            'isPreview' => !$category->is_active && $isAdmin,
            'seo' => $seo,
        ]);
    }
}

