<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'address',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'school_type',
        'district_name',
        'district_id',
        'facility_type',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'latitude'  => 'float',
            'longitude' => 'float',
            'is_public' => 'boolean',
        ];
    }

    public function catchments(): HasMany
    {
        return $this->hasMany(SchoolCatchment::class, 'school_id');
    }
}
