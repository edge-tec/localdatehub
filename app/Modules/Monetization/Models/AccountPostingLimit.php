<?php

namespace App\Modules\Monetization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountPostingLimit extends Model
{
    use HasFactory;

    protected $table = 'account_posting_limits';

    protected $fillable = [
        'account_type',
        'daily_limit',
        'weekly_limit',
        'monthly_limit',
        'free_post_quota',
        'is_unlimited',
        'post_price_credits',
        'approval_mode',
    ];

    protected function casts(): array
    {
        return [
            'daily_limit' => 'integer',
            'weekly_limit' => 'integer',
            'monthly_limit' => 'integer',
            'free_post_quota' => 'integer',
            'is_unlimited' => 'boolean',
            'post_price_credits' => 'integer',
        ];
    }
}

