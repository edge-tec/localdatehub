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

        return view('search.index', [
            'filter' => $filter,
            'listings' => $listings,
            'categories' => $categories,
            'states' => $states,
            'categoryCounts' => $categoryCounts,
            'activeDriver' => $activeDriver,
        ]);
    }
}
