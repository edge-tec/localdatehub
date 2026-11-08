<?php

namespace App\Modules\Moderation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StateCompliance extends Model
{
    protected $fillable = [
        'state_code',
        'state_name',
        'requires_age_gate',
        'requires_id_verification',
        'statutory_min_age',
        'mandatory_warning_text',
        'is_restricted',
        'restricted_categories',
        'legal_reference',
    ];

    protected function casts(): array
    {
        return [
            'requires_age_gate' => 'boolean',
            'requires_id_verification' => 'boolean',
            'statutory_min_age' => 'integer',
            'is_restricted' => 'boolean',
            'restricted_categories' => 'array',
        ];
    }

    public static function forCode(string $stateCode): ?self
    {
        return static::where('state_code', strtoupper($stateCode))->first();
    }

    public function scopeRequiresIdCheck(Builder $query): Builder
    {
        return $query->where('requires_id_verification', true);
    }
}

