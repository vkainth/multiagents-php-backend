<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShowingRequests extends Model
{



    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'showing_requests';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['email', 'first', 'last', 'phone', 'language', 'working_with_agent','pre_approved_mortgage','date1','time1','date2','time2','notes','site','mls','created_at','updated_at'];

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
