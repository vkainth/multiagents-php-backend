<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserPropertyViews extends Model
{

    protected $table = 'user_property_views';

    protected $fillable = ['userid', 'uid', 'mls', 'status', 'price', 'created_at', 'updated_at', 'ref', 'header', 'user_agent', 'device', 'country', 'ip'];

    protected $hidden = [];

    protected $dates = []; // created_at,updated_at default-included

    protected function casts(): array
    {
        return [];
    }


    public function property(): HasOne
    {
        return $this->hasOne(\App\Models\Listings::class, 'listingid', 'mls');
    }

}
