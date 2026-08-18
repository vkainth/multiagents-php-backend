<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CondoImages extends Model  {

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql_pixi360';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'condo_images';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['condo_id', 'strata_no', 'strata_idx', 'source_id', 'image_name', 'added', 'added_by', 'order', 'width', 'height', 'max_image_id'];

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
    protected $dates = ['added'];

}