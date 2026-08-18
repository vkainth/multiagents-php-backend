<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photos extends Model  {

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql_boards';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'photos';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['table', 'sysid', 'name', 'directory', 'date', 'type', 'text', 'extension', 'size', 'width', 'height', 'status'];

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
    protected $dates = ['date'];

    public function listing(){
        $this->belongsTo('App\Models\Listings', 'sysid', 'sysid');
    }

}
