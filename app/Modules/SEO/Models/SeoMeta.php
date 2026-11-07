<?php

namespace App\Modules\SEO\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_route',
        'model_type',
        'model_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_image_url',
        'robots_directive',
        'structured_data',
    ];

    protected function casts(): array
    {
        return [
            'structured_data' => 'array',
        ];
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
