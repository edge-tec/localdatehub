<?php

namespace App\Http\Controllers;

use App\Modules\Categories\Services\CategoryService;
use App\Modules\Locations\Models\State;
use App\Modules\Search\Requests\SearchFilterRequest;
use App\Modules\Search\Services\SearchService;
use Illuminate\View\View;

class SearchWebController extends Controller
{
    public function __construct(
        protected SearchService $searchService,
        protected CategoryService $categoryService
    ) {}

    /**
     * Display the advanced search results page with multi-parameter filter sidebar.
     */
    public function index(SearchFilterRequest $request): View
    {
        $filter = $request->toDTO();
        $listings = $this->searchService->searchListings($filter, 15);
        $categories = $this->categoryService->getActiveCategories();
        $states = State::where('is_active', true)->orderBy('name')->get();
        $categoryCounts = $this->searchService->getCategoryCounts($filter);
        $activeDriver = $this->searchService->getActiveDriverName();

        $adContext = array_filter([
            'state' => $filter->stateCode ?? null,
            'city_id' => $filter->cityId ?? null,
            'zip_code' => $filter->zipCode ?? null,
            'category_id' => $filter->categoryId ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $searchFeedItems = app(\App\Modules\Advertising\Services\AdDeliveryService::class)
            ->injectIntoListings($listings->getCollection(), \App\Modules\Advertising\Models\AdCampaign::SLOT_SEARCH_RESULTS, $adContext);

        return view('search.index', [
            'filter' => $filter,
            'listings' => $listings,
            'searchFeedItems' => $searchFeedItems,
            'adContext' => $adContext,
            'categories' => $categories,
            'states' => $states,
            'categoryCounts' => $categoryCounts,
            'activeDriver' => $activeDriver,
        ]);
    }
}
