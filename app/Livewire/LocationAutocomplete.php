<?php

namespace App\Livewire;

use App\Modules\Locations\Services\LocationService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class LocationAutocomplete extends Component
{
    public string $query = '';
    public string $placeholder = 'Search city, state, ZIP, or region (e.g. Austin, DFW, 78701)...';
    public bool $redirectOnSelect = true;
    public int $selectedIndex = -1;
    public bool $isOpen = false;

    public function updatedQuery(): void
    {
        $this->selectedIndex = -1;
        $this->isOpen = strlen(trim($this->query)) >= 2;
    }

    public function selectItem(int $index): void
    {
        $results = $this->getResultsProperty();
        if (isset($results[$index])) {
            $item = $results[$index];
            $this->selectLocation($item);
        }
    }

    public function selectLocation(array $item): void
    {
        $this->query = $item['title'];
        $this->isOpen = false;

        $this->dispatch('location-selected', location: $item);

        if ($this->redirectOnSelect) {
            if ($item['type'] === 'state') {
                $this->redirect(route('locations.state', ['stateCode' => $item['state_code']]));
            } elseif ($item['city_slug'] && $item['state_code']) {
                $this->redirect(route('locations.city', [
                    'stateCode' => $item['state_code'],
                    'citySlug' => $item['city_slug'],
                ]));
            }
        }
    }

    public function selectHighlighted(): void
    {
        $index = $this->selectedIndex >= 0 ? $this->selectedIndex : 0;
        $this->selectItem($index);
    }

    public function incrementHighlight(): void
    {
        $count = count($this->getResultsProperty());
        if ($count === 0) return;
        $this->selectedIndex = ($this->selectedIndex + 1) % $count;
    }

    public function decrementHighlight(): void
    {
        $count = count($this->getResultsProperty());
        if ($count === 0) return;
        $this->selectedIndex = ($this->selectedIndex - 1 + $count) % $count;
    }

    public function getResultsProperty(): Collection
    {
        if (strlen(trim($this->query)) < 2) {
            return collect();
        }

        return app(LocationService::class)->searchAutocomplete($this->query, 8);
    }

    public function render(): View
    {
        return view('livewire.location-autocomplete', [
            'results' => $this->isOpen ? $this->getResultsProperty() : collect(),
        ]);
    }
}

