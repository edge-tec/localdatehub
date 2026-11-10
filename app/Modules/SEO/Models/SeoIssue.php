<?php

namespace App\Modules\SEO\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoIssue extends Model
{
    use HasFactory;

    protected $table = 'seo_issues';

    protected $fillable = [
        'url_record_id',
        'url',
        'issue_type',
        'severity',
        'description',
        'recommendation',
        'is_resolved',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function urlRecord(): BelongsTo
    {
        return $this->belongsTo(SeoUrlRecord::class, 'url_record_id');
    }
}

