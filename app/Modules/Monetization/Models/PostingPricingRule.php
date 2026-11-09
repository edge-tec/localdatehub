<?php

namespace App\Modules\Monetization\Models;

use App\Modules\Categories\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostingPricingRule extends Model
{
    use HasFactory;

    protected $table = 'posting_pricing_rules';

    protected $fillable = [
        'category_id',
        'account_type',
        'price_credits',
        'is_free',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_credits' => 'integer',
            'is_free' => 'boolean',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

