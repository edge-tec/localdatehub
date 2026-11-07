<?php

namespace App\Http\Controllers;

use App\Modules\Categories\Services\CategoryService;
use App\Modules\Locations\Models\State;
use App\Modules\Search\DTOs\SearchFilterDTO;
use App\Modules\Search\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchWebController extends Controller
{
    public function __construct(
        protected SearchService $searchService,
        protected CategoryService $categoryService
    ) {}

    /**
     * Display the search results page with filter sidebar.
     */
    public function index(Request $request): View
    {
        $filter = SearchFilterDTO::fromArray($request->all());
        $listings = $this->searchService->searchListings($filter, 15);
        $categories = $this->categoryService->getActiveCategories();
        $states = State::where('is_supported', true)->orderBy('name')->get();

        return view('search.index', [
            'filter' => $filter,
            'listings' => $listings,
            'categories' => $categories,
            'states' => $states,
        ]);
    }
}
