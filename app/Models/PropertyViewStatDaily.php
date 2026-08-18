<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyViewStatDaily extends Model  {

    

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'property_view_stat_daily';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['userid', 'city', 'area', 'status', 'min_price', 'max_price', 'total_listings', 'mls', 'day', 'month', 'year', 'weekly_processed', 'updated_at'];

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
    protected $casts = ['weekly_processed' => 'boolean'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['updated_at'];

}