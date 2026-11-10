<?php

namespace App\Modules\SEO\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoKeywordGroup extends Model
{
    use HasFactory;

    protected $table = 'seo_keyword_groups';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function keywords(): HasMany
    {
        return $this->hasMany(SeoKeyword::class, 'group_id');
    }
}

