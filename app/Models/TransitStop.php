<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransitStop extends Model
{
    protected $fillable = [
        'stop_id',
        'stop_name',
        'latitude',
        'longitude',
        'routes',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'latitude'  => 'float',
            'longitude' => 'float',
            'routes'    => 'array',
        ];
    }
}
