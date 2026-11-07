<?php

namespace App\Modules\Accounts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'consent_type',
        'version',
        'is_accepted',
        'ip_address',
        'user_agent',
        'consented_at',
    ];

    protected function casts(): array
    {
        return [
            'is_accepted' => 'boolean',
            'consented_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
