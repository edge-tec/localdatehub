<?php

namespace App\Livewire;

use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Services\LocationService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class DependentLocationSelector extends Component
{
    public ?string $selectedState = null;
    public ?int $selectedCityId = null;
    public string $zipCode = '';
    public string $stateSearch = '';
    public string $citySearch = '';
    public bool $includeTerritories = true;
    public ?string $fieldNamePrefix = null; // e.g. 'listing[location]'

    public function mount(
        ?string $state = null,
        ?int $cityId = null,
        string $zip = '',
        bool $includeTerritories = true,
        ?string $fieldNamePrefix = null
    ): void {
        $this->selectedState = $state ? strtoupper($state) : null;
        $this->selectedCityId = $cityId;
        $this->zipCode = $zip;
        $this->includeTerritories = $includeTerritories;
        $this->fieldNamePrefix = $fieldNamePrefix;
    }

    public function updatedSelectedState(): void
    {
        $this->selectedCityId = null;
        $this->citySearch = '';
        $this->dispatchSelection();
    }

    public function updatedSelectedCityId(): void
    {
        $this->dispatchSelection();
    }

    /**
     * Auto-resolve location when a 5-digit ZIP code is entered.
     */
    public function updatedZipCode(): void
    {
        $cleanZip = trim($this->zipCode);
        if (preg_match('/^\d{5}$/', $cleanZip)) {
            $localities = app(LocationService::class)->lookupZipCode($cleanZip);
            if ($localities->isNotEmpty()) {
                $primary = $localities->first();
                $this->selectedState = $primary['state_code'];
                $this->selectedCityId = $primary['city_id'];
                $this->dispatchSelection();
            }
        }
    }

    public function selectState(string $code): void
    {
        $this->selectedState = strtoupper($code);
        $this->selectedCityId = null;
        $this->dispatchSelection();
    }

    public function selectCity(int $cityId): void
    {
        $this->selectedCityId = $cityId;
        $this->dispatchSelection();
    }

    protected function dispatchSelection(): void
    {
        $city = $this->selectedCityId ? City::find($this->selectedCityId) : null;

        $this->dispatch('location-changed', [
            'state_code' => $this->selectedState,
            'city_id' => $this->selectedCityId,
            'city_name' => $city?->name,
            'city_slug' => $city?->slug,
            'zip_code' => $this->zipCode,
        ]);
    }

    public function getStatesProperty(): Collection
    {
        $query = State::where('is_active', true);
        if (!$this->includeTerritories) {
            $query->where('is_territory', false);
        }

        if (!empty($this->stateSearch)) {
            $s = trim($this->stateSearch);
            $query->where(function ($q) use ($s) {
                $q->where('code', strtoupper($s))
                  ->orWhere('name', 'like', "%{$s}%");
            });
        }

        return $query->orderBy('is_territory')->orderBy('name')->get();
    }

    public function getCitiesProperty(): Collection
    {
        if (!$this->selectedState) {
            return collect();
        }

        $state = State::where('code', $this->selectedState)->first();
        if (!$state) {
            return collect();
        }

        $query = City::where('state_id', $state->id)->where('is_active', true);

        if (!empty($this->citySearch)) {
            $c = trim($this->citySearch);
            $query->where('name', 'like', "%{$c}%");
        }

        return $query->orderBy('is_popular', 'desc')->orderBy('population', 'desc')->get();
    }

    public function render(): View
    {
        return view('livewire.dependent-location-selector', [
            'states' => $this->getStatesProperty(),
            'cities' => $this->getCitiesProperty(),
        ]);
    }
}

