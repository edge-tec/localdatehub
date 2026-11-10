<?php

namespace App\Modules\SEO\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoContentSuggestion extends Model
{
    use HasFactory;

    protected $table = 'seo_content_suggestions';

    protected $fillable = [
        'topic',
        'target_type',
        'target_id',
        'suggested_title',
        'suggested_keywords',
        'search_intent',
        'faq_suggestions',
        'internal_link_targets',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'suggested_keywords' => 'array',
            'faq_suggestions' => 'array',
            'internal_link_targets' => 'array',
        ];
    }
}

