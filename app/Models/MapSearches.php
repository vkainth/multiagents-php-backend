<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapSearches extends Model  {

    

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'map_searches';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['userid', 'uid', 'data', 'status', 'agent_id', 'mls', 'city', 'subarea', 'filters', 'processed', 'search_stat_processed', 'created_at', 'updated_at', 'hash', 'ip', 'country'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = ['processed' => 'boolean', 'search_stat_processed' => 'boolean'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

}