<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearches extends Model  {

    

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'saved_searches';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['userid', 'email', 'search_name', 'data', 'daily_email','last_update_sent', 'listing_sql','search_url','created_at','just_listed_alert','just_sold_alert','confirmed','active','confirmation_token','manage_token'];

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