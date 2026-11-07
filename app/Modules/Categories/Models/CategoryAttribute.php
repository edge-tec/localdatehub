<?php

namespace App\Modules\Categories\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'code',
        'field_type',
        'is_required',
        'is_searchable',
        'is_filterable',
        'display_order',
        'validation_rules',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_searchable' => 'boolean',
            'is_filterable' => 'boolean',
            'display_order' => 'integer',
            'validation_rules' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(CategoryAttributeOption::class)->orderBy('display_order');
    }
}
