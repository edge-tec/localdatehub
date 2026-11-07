<?php

namespace App\Modules\Moderation\Models;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmcaRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_TAKEDOWN_ISSUED = 'takedown_issued';
    public const STATUS_COUNTER_NOTICED = 'counter_noticed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'ticket_number',
        'claimant_name',
        'claimant_email',
        'claimant_phone',
        'organization',
        'copyrighted_work',
        'infringing_url',
        'target_listing_id',
        'good_faith_attested',
        'perjury_attested',
        'digital_signature',
        'status',
        'counter_notice_details',
        'reviewed_by_admin_id',
        'reviewed_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'good_faith_attested' => 'boolean',
            'perjury_attested' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function targetListing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'target_listing_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
