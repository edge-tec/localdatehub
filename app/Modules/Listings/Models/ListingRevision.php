<?php

namespace App\Modules\Listings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingRevision extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'listing_id',
        'user_id',
        'title',
        'description',
        'attributes_snapshot',
        'change_summary',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'attributes_snapshot' => 'array',
            'created_at' => 'datetime',
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
}
