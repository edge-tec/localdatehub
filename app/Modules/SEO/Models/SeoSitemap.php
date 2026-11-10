<?php

namespace App\Modules\SEO\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoSitemap extends Model
{
    use HasFactory;

    protected $table = 'seo_sitemaps';

    protected $fillable = [
        'name',
        'file_path',
        'url_count',
        'file_size_bytes',
        'is_valid_xml',
        'validation_error',
        'last_generated_at',
        'last_submitted_at',
        'submission_status',
    ];

    protected function casts(): array
    {
        return [
            'url_count' => 'integer',
            'file_size_bytes' => 'integer',
            'is_valid_xml' => 'boolean',
            'last_generated_at' => 'datetime',
            'last_submitted_at' => 'datetime',
        ];
    }
}

