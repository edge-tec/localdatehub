<?php

namespace App\Modules\Listings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'user_id',
        'file_path',
        'thumbnail_path',
        'original_filename',
        'mime_type',
        'file_size_bytes',
        'width',
        'height',
        'is_primary',
        'display_order',
        'moderation_status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'display_order' => 'integer',
            'file_size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): string
    {
        if (empty($this->file_path)) {
            return asset('images/featured-1.jpg');
        }
        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://') || str_starts_with($this->file_path, '/')) {
            return $this->file_path;
        }
        return asset('storage/' . $this->file_path);
    }
}
