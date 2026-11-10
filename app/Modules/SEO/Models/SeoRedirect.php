<?php

namespace App\Modules\SEO\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoRedirect extends Model
{
    use HasFactory;

    protected $table = 'seo_redirects';

    protected $fillable = [
        'source_path',
        'target_url',
        'status_code',
        'is_active',
        'hits',
        'last_hit_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
        ];
    }
}

