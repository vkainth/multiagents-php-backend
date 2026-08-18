<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuildingFollow extends Model
{
    protected $table = 'building_follows';

    protected $fillable = [
        'userid', 'email', 'building_slug', 'building_name',
        'street_no', 'street_name', 'city', 'strata_no',
        'confirmed', 'active', 'confirmation_token',
        'manage_token', 'last_update_sent', 'created_at',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(\App\Models\Auth\FirebaseUser::class, 'userid', 'id');
    }
}
