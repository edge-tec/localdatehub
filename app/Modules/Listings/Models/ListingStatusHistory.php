<?php

namespace App\Modules\Listings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingStatusHistory extends Model
{
    use HasFactory;

    public $table = 'listing_status_history';
    public $timestamps = false;

    protected $fillable = [
        'listing_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by_user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
