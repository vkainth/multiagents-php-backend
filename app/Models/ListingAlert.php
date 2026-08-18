<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingAlert extends Model
{
    protected $fillable = ['email', 'city', 'subarea', 'type', 'source', 'ip_hash'];
}
