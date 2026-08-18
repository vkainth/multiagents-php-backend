<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferlandPrice extends Model
{
    // protected $connection = 'pixilink_360'; // no-configured
    // protected $connection_360 = 'mysql_pixi360'; // Base table or view not found: 1146 Table 'bccondosandhomes.offerland_prices' doesn't exist
    // protected $connection = 'mysql'; // Base table or view not found: 1146 Table 'bccondosandhomes.offerland_prices' doesn't exist
    protected $connection = 'mysql_mlsr'; // working-connection to database-pixilink_mlsr

    // protected $table = 'pixilink_mlsr.offerland_prices';

    protected $fillable = ['ml_no','offer_value','estimated_date_time','created_at','updated_at'];

    // protected $dates = ['estimated_date_time','created_at','updated_at'];
    
    // protected $casts = ['ml_no'=>'string','offer_value'=>'string','estimated_date_time'=>'string','created_at'=>'string','updated_at'=>'string'];

    ##########################################################


}
