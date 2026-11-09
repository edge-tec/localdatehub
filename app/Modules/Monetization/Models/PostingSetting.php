<?php

namespace App\Modules\Monetization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostingSetting extends Model
{
    use HasFactory;

    protected $table = 'posting_settings';

    protected $fillable = [
        'global_posting_mode',
        'default_post_price_credits',
        'default_free_quota',
        'unlimited_posting_enabled',
        'daily_limit',
        'weekly_limit',
        'monthly_limit',
        'approval_mode',
        'new_account_manual_review',
        'verified_user_auto_approve',
        'verified_user_ranking_boost',
    ];

    protected function casts(): array
    {
        return [
            'default_post_price_credits' => 'integer',
            'default_free_quota' => 'integer',
            'unlimited_posting_enabled' => 'boolean',
            'daily_limit' => 'integer',
            'weekly_limit' => 'integer',
            'monthly_limit' => 'integer',
            'new_account_manual_review' => 'boolean',
            'verified_user_auto_approve' => 'boolean',
            'verified_user_ranking_boost' => 'integer',
        ];
    }

    /**
     * Retrieve the singleton instance of posting settings.
     */
    public static function instance(): self
    {
        return static::firstOrCreate([], [
            'global_posting_mode' => 'quota_then_paid',
            'default_post_price_credits' => 10,
            'default_free_quota' => 3,
            'unlimited_posting_enabled' => false,
            'daily_limit' => 5,
            'weekly_limit' => 20,
            'monthly_limit' => 50,
            'approval_mode' => 'conditional',
            'new_account_manual_review' => true,
            'verified_user_auto_approve' => true,
            'verified_user_ranking_boost' => 25,
        ]);
    }
}

