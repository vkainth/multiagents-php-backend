<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolCatchment extends Model
{
    protected $fillable = [
        'school_id',
        'level',
        'district_id',
        'catchment_name',
        'polygon_geojson',
        'polygon_wkt',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
