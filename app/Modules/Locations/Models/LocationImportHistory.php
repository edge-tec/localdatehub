<?php

namespace App\Modules\Locations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationImportHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_name',
        'version',
        'source_file',
        'checksum',
        'records_processed',
        'records_created',
        'records_updated',
        'status',
        'duration_ms',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'records_processed' => 'integer',
            'records_created' => 'integer',
            'records_updated' => 'integer',
            'duration_ms' => 'integer',
        ];
    }
}
