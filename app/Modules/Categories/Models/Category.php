<?php

namespace App\Modules\Categories\Models;

use App\Modules\Listings\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Category extends Model
{
    use HasFactory;

    /**
     * Maximum depth permitted for hierarchical categories to prevent runaway recursion.
     */
    public const MAX_DEPTH = 5;

    protected $fillable = [
        'parent_id',
        'depth',
        'name',
        'slug',
        'code',
        'description',
        'safety_guidelines',
        'icon',
        'min_age_override',
        'display_order',
        'is_active',
        'is_featured',
        'allowed_contact_methods',
        'seo_metadata',
    ];

    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'display_order' => 'integer',
            'min_age_override' => 'integer',
            'allowed_contact_methods' => 'array',
            'seo_metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            // Auto calculate depth
            if ($category->parent_id) {
                if ($category->id && $category->parent_id === $category->id) {
                    throw new InvalidArgumentException('A category cannot be its own parent.');
                }

                $parent = Category::find($category->parent_id);
                if ($parent) {
                    $category->depth = $parent->depth + 1;
                    if ($category->depth > self::MAX_DEPTH) {
                        throw new InvalidArgumentException(
                            "Category hierarchy cannot exceed maximum depth of " . self::MAX_DEPTH . "."
                        );
                    }
                }
            } else {
                $category->depth = 0;
            }
        });
    }

    // Direct Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('display_order');
    }

    public function subcategories(): HasMany
    {
        return $this->children();
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Recursive eager-loading of all descendants.
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function allActiveChildren(): HasMany
    {
        return $this->children()->where('is_active', true)->with('allActiveChildren');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class)->orderBy('display_order');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    // Traversal Helpers
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    /**
     * Get all ancestor categories in order from root down to parent.
     *
     * @return \Illuminate\Support\Collection<int, Category>
     */
    public function ancestors(): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get flat collection of all descendants at any depth.
     *
     * @return \Illuminate\Support\Collection<int, Category>
     */
    public function descendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    /**
     * Return all category IDs including self and all recursive descendants.
     *
     * @return array<int>
     */
    public function descendantIds(bool $includeSelf = true): array
    {
        $ids = $this->descendants()->pluck('id')->all();
        if ($includeSelf) {
            array_unshift($ids, $this->id);
        }
        return $ids;
    }

    /**
     * Get combined attributes: global attributes (category_id is null), ancestor attributes, and local attributes.
     *
     * @return \Illuminate\Support\Collection<int, CategoryAttribute>
     */
    public function allAttributes(): \Illuminate\Support\Collection
    {
        $categoryIds = $this->ancestors()->pluck('id')->push($this->id)->all();

        return CategoryAttribute::with('options')
            ->where(function ($q) use ($categoryIds) {
                $q->whereNull('category_id')
                  ->orWhereIn('category_id', $categoryIds);
            })
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Breadcrumbs from root to current category.
     *
     * @return array<int, array{name: string, slug: string, url: string}>
     */
    public function getBreadcrumbs(): array
    {
        $crumbs = [];
        foreach ($this->ancestors() as $ancestor) {
            $crumbs[] = [
                'name' => $ancestor->name,
                'slug' => $ancestor->slug,
                'url' => route('categories.show', $ancestor->slug),
            ];
        }

        $crumbs[] = [
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => route('categories.show', $this->slug),
        ];

        return $crumbs;
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->orderBy('display_order');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeActiveTree(Builder $query): Builder
    {
        return $query->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['allActiveChildren.attributes.options'])
            ->orderBy('display_order');
    }
}
