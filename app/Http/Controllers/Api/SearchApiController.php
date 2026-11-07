<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\ZipCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchApiController extends Controller
{
    /**
     * Provide combined typeahead suggestions for search inputs (cities, zips, categories).
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (strlen($query) < 2) {
            return response()->json([
                'cities' => [],
                'zips' => [],
                'categories' => [],
            ]);
        }

        $cities = City::with('state')
            ->where('name', 'like', "{$query}%")
            ->orderBy('is_popular', 'desc')
            ->orderBy('name')
            ->take(6)
            ->get(['id', 'name', 'slug', 'state_id', 'latitude', 'longitude'])
            ->map(fn ($city) => [
                'id' => $city->id,
                'name' => $city->name,
                'state_code' => $city->state?->code,
                'state_name' => $city->state?->name,
                'label' => "{$city->name}, {$city->state?->code}",
                'lat' => $city->latitude,
                'lng' => $city->longitude,
            ]);

        $zips = ZipCode::with('city.state')
            ->where('zip_code', 'like', "{$query}%")
            ->take(5)
            ->get(['id', 'zip_code', 'city_id', 'latitude', 'longitude'])
            ->map(fn ($zip) => [
                'id' => $zip->id,
                'zip_code' => $zip->zip_code,
                'city_name' => $zip->city?->name,
                'state_code' => $zip->city?->state?->code,
                'label' => "{$zip->zip_code} — {$zip->city?->name}, {$zip->city?->state?->code}",
                'lat' => $zip->latitude,
                'lng' => $zip->longitude,
            ]);

        $categories = Category::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->take(5)
            ->get(['id', 'name', 'slug', 'parent_id'])
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ]);

        return response()->json([
            'cities' => $cities,
            'zips' => $zips,
            'categories' => $categories,
        ]);
    }
}
