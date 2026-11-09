<?php

namespace App\Modules\Monetization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPostingOverride extends Model
{
    use HasFactory;

    protected $table = 'user_posting_overrides';

    protected $fillable = [
        'user_id',
        'custom_quota',
        'free_post_allowance',
        'is_unlimited',
        'custom_price_credits',
        'approval_mode',
        'is_posting_restricted',
        'restriction_reason',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_quota' => 'integer',
            'free_post_allowance' => 'integer',
            'is_unlimited' => 'boolean',
            'custom_price_credits' => 'integer',
            'is_posting_restricted' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

