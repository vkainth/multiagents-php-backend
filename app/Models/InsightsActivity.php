<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsightsActivity extends Model  {

    

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_insight_views';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['userid', 'activity', 'activity_label' , 'city','subarea', 'period', 'ref', 'created_at','user_agent','device'];

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
    protected $casts = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at'];
    public $timestamps = false;
}