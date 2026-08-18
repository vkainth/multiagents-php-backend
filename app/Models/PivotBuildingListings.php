<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PivotBuildingListings extends Pivot
{
    protected $connection = 'mysql_mlsr';
    protected $connection_360 = 'mysql_pixi360';

    protected $table = 'pixilink_mlsr.vu_pivot_building_listings';
}
