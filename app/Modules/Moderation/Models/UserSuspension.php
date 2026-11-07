<?php

namespace App\Modules\Moderation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSuspension extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'issued_by_admin_id',
        'reason',
        'notes',
        'suspension_type',
        'starts_at',
        'ends_at',
        'is_lifted',
        'lifted_reason',
        'lifted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_lifted' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'lifted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_admin_id');
    }

    public function appeals(): HasMany
    {
        return $this->hasMany(AppealRequest::class);
    }
}
