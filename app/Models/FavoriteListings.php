<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\FavoriteListingsGlobalScope;
use Illuminate\Database\Eloquent\Casts\Attribute; //lv-11
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\Scopes\BuildingsGlobalFilterScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([FavoriteListingsGlobalScope::class])]
class FavoriteListings extends Model  
{

    protected $table = 'favorite_listings';

    protected $fillable = ['userid', 'listingid', 'status','price', 'last_update_sent', 'created_at','ip','country','deleted','watch_price_drop','watch_sold'];

    protected $dates = ['created_at'];

    protected $hidden = []; // The attributes excluded from the model's JSON form.

    protected function casts(): array
    {
        return [];
    }


    public $timestamps = false;

    public function listing(): HasOne
    {
        return $this->hasOne(\App\Models\Listings::class, 'listingid', 'listingid');
    }

    public function user(): HasOne
    {
        return $this->hasOne(\App\Models\Auth\FirebaseUser::class, 'id', 'userid');
    }

    public function photos(): HasManyThrough
    {
        return $this->hasManyThrough(
            \App\Models\Photos::class,
            \App\Models\Listings::class,
            'listingid', // Foreign key on Listings table
            'sysid', // Foreign key on Photos table
            'listingid', // Local key on favorite_listings table
            'sysid' // Local key on Listings table
        );
    }

    /**
     * aphoto [created:2024] only fetch-one-photo_(as Collection)
     * @return Collection [description]
     */
    public function aphoto()
    {
        return $this->photos()->limit(1)->one();
    }
 

}
