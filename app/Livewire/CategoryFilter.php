<?php

namespace App\Livewire;

use App\Modules\Categories\Models\Category;
use Livewire\Component;

class CategoryFilter extends Component
{
    public ?int $selectedRootId = null;
    public ?int $selectedGroupId = null;
    public ?int $selectedSubcategoryId = null;
    public string $selectedCategorySlug = '';

    public array $rootCategories = [];
    public array $groupCategories = [];
    public array $subcategories = [];

    public function mount(?string $initialSlug = null): void
    {
        $this->loadRoots();

        if ($initialSlug) {
            $cat = Category::where('slug', $initialSlug)->first();
            if ($cat) {
                $this->selectByModel($cat);
            }
        }
    }

    public function loadRoots(): void
    {
        $roots = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['activeChildren'])
            ->orderBy('display_order')
            ->get();

        $this->rootCategories = $roots->map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'slug' => $r->slug,
        ])->all();

        if (!empty($this->rootCategories) && !$this->selectedRootId) {
            $this->selectRoot($this->rootCategories[0]['id']);
        }
    }

    public function selectRoot(int $rootId): void
    {
        $this->selectedRootId = $rootId;
        $this->selectedGroupId = null;
        $this->selectedSubcategoryId = null;

        $groups = Category::where('parent_id', $rootId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $this->groupCategories = $groups->map(fn ($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'slug' => $g->slug,
            'subcategories_count' => $g->children()->where('is_active', true)->count(),
        ])->all();

        $this->subcategories = [];
    }

    public function selectGroup(int $groupId): void
    {
        $this->selectedGroupId = $groupId;
        $this->selectedSubcategoryId = null;

        $group = Category::find($groupId);
        if ($group) {
            $this->selectedCategorySlug = $group->slug;
            $this->dispatch('category-selected', slug: $group->slug, id: $group->id);
        }

        $subs = Category::where('parent_id', $groupId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $this->subcategories = $subs->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'slug' => $s->slug,
            'code' => $s->code,
        ])->all();
    }

    public function selectSubcategory(int $subId): void
    {
        $this->selectedSubcategoryId = $subId;
        $sub = Category::find($subId);
        if ($sub) {
            $this->selectedCategorySlug = $sub->slug;
            $this->dispatch('category-selected', slug: $sub->slug, id: $sub->id);
        }
    }

    protected function selectByModel(Category $cat): void
    {
        if ($cat->depth === 2 && $cat->parent) {
            $this->selectedRootId = $cat->parent->parent_id;
            $this->selectGroup($cat->parent_id);
            $this->selectedSubcategoryId = $cat->id;
        } elseif ($cat->depth === 1) {
            $this->selectedRootId = $cat->parent_id;
            $this->selectGroup($cat->id);
        } else {
            $this->selectRoot($cat->id);
        }
        $this->selectedCategorySlug = $cat->slug;
    }

    public function render()
    {
        return view('livewire.category-filter');
    }
}
