<?php

namespace App\Modules\Accounts\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'display_name',
        'bio',
        'gender',
        'seeking',
        'birthdate',
        'city',
        'state_code',
        'zip_code',
        'avatar_path',
        'social_handles',
        'hide_location_details',
        'hide_age',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'social_handles' => 'array',
            'hide_location_details' => 'boolean',
            'hide_age' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birthdate ? Carbon::parse($this->birthdate)->age : null;
    }
}
