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
        return $this->resolveAssetUrl($this->file_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->resolveAssetUrl($this->thumbnail_path ?: $this->file_path);
    }

    /**
     * Normalize relative, rooted, and absolute media paths into a consistent public URL.
     */
    protected function resolveAssetUrl(?string $path): string
    {
        $trimmed = trim((string) $path);
        if ($trimmed === '') {
            return asset('images/featured-1.jpg');
        }
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }
        if (str_starts_with($trimmed, '/')) {
            return asset(ltrim($trimmed, '/'));
        }
        if (str_starts_with($trimmed, 'images/')) {
            return asset($trimmed);
        }

        return asset('storage/' . ltrim($trimmed, '/'));
    }
}
