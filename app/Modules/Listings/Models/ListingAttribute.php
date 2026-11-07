<?php

namespace App\Modules\Listings\Models;

use App\Modules\Categories\Models\CategoryAttribute;
use App\Modules\Categories\Models\CategoryAttributeOption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'category_attribute_id',
        'category_attribute_option_id',
        'attribute_code',
        'value_text',
        'value_number',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_number' => 'decimal:4',
            'value_json' => 'array',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function categoryAttribute(): BelongsTo
    {
        return $this->belongsTo(CategoryAttribute::class);
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(CategoryAttributeOption::class, 'category_attribute_option_id');
    }
}
