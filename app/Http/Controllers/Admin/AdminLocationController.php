<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\LocationAlias;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLocationController extends Controller
{
    /**
     * Authorize admin access.
     */
    protected function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'super_admin'])) {
            abort(403, 'Unauthorized administrative access.');
        }
    }

    /**
     * Display the administrative location management dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $query = City::with(['state', 'county', 'aliases'])->withCount('zipCodes');

        if ($request->filled('state')) {
            $query->whereHas('state', fn ($q) => $q->where('code', strtoupper($request->input('state'))));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "{$search}%")
                  ->orWhere('slug', 'like', "{$search}%")
                  ->orWhereHas('aliases', fn ($aq) => $aq->where('alias', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('popular')) {
            $query->where('is_popular', $request->boolean('popular'));
        }

        $cities = $query->orderBy('name')->paginate(25)->withQueryString();
        $states = State::where('is_active', true)->orderBy('name')->get();

        return view('admin.locations.index', [
            'cities' => $cities,
            'states' => $states,
        ]);
    }

    /**
     * Update an existing city record.
     */
    public function updateCity(Request $request, City $city): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'population' => ['nullable', 'integer', 'min:0'],
            'timezone' => ['required', 'string', 'max:50'],
            'is_popular' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $city->update([
            'name' => $validated['name'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'population' => $validated['population'] ?? null,
            'timezone' => $validated['timezone'],
            'is_popular' => $request->boolean('is_popular'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', "City [{$city->name}] updated successfully.");
    }

    /**
     * Add a metropolitan or colloquial alias to a city.
     */
    public function storeAlias(Request $request, City $city): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'alias' => ['required', 'string', 'max:100'],
            'alias_type' => ['required', 'in:metro_area,neighborhood,colloquial'],
        ]);

        LocationAlias::create([
            'city_id' => $city->id,
            'alias' => trim($validated['alias']),
            'alias_type' => $validated['alias_type'],
        ]);

        return back()->with('status', "Alias '{$validated['alias']}' added to {$city->name}.");
    }

    /**
     * Delete a location alias.
     */
    public function destroyAlias(Request $request, LocationAlias $alias): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $cityName = $alias->city?->name ?? 'City';
        $aliasText = $alias->alias;
        $alias->delete();

        return back()->with('status', "Alias '{$aliasText}' removed from {$cityName}.");
    }

    /**
     * Associate a 5-digit ZIP code with a city.
     */
    public function storeZip(Request $request, City $city): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'zip_code' => ['required', 'regex:/^\d{5}$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        ZipCode::firstOrCreate(
            [
                'zip_code' => $validated['zip_code'],
                'city_id' => $city->id,
            ],
            [
                'state_id' => $city->state_id,
                'latitude' => $validated['latitude'] ?? $city->latitude,
                'longitude' => $validated['longitude'] ?? $city->longitude,
            ]
        );

        return back()->with('status', "ZIP code {$validated['zip_code']} linked to {$city->name}.");
    }
}
