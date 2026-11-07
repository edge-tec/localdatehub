<?php

namespace App\Modules\Categories\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryAttribute extends Model
{
    use HasFactory;

    /**
     * Allowed dynamic field types.
     */
    public const FIELD_TYPES = [
        'text',
        'textarea',
        'number',
        'select',
        'multiselect',
        'radio',
        'boolean',
        'range',
    ];

    protected $fillable = [
        'category_id',
        'name',
        'placeholder',
        'help_text',
        'unit',
        'default_value',
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

    public function scopeSearchable(Builder $query): Builder
    {
        return $query->where('is_searchable', true)->orderBy('display_order');
    }

    public function scopeFilterable(Builder $query): Builder
    {
        return $query->where('is_filterable', true)->orderBy('display_order');
    }

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    /**
     * Build Laravel validation rule string or array for this attribute.
     *
     * @return array<int, mixed>
     */
    public function getValidationRuleList(): array
    {
        $rules = [];

        if ($this->is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        switch ($this->field_type) {
            case 'number':
            case 'range':
                $rules[] = 'numeric';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'select':
            case 'radio':
                $rules[] = 'string';
                $validValues = $this->options->pluck('value')->all();
                if (!empty($validValues)) {
                    $rules[] = 'in:' . implode(',', $validValues);
                }
                break;
            case 'multiselect':
                $rules[] = 'array';
                break;
            case 'textarea':
                $rules[] = 'string';
                $rules[] = 'max:3000';
                break;
            case 'text':
            default:
                $rules[] = 'string';
                $rules[] = 'max:255';
                break;
        }

        if (!empty($this->validation_rules) && is_array($this->validation_rules)) {
            foreach ($this->validation_rules as $rule) {
                if (!in_array($rule, $rules, true)) {
                    $rules[] = $rule;
                }
            }
        }

        return $rules;
    }
}
