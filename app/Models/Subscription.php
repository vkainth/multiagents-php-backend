<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Subscription extends Model  {
    
    protected $table = 'subscriptions';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'name', 'type', 'stripe_id', 'stripe_plan', 'stripe_price',  'quantity', 'trial_ends_at', 'ends_at', 'created_at', 'updated_at', 'stripe_confirmed', 'user_stripe_id', 'user_stripe_email'];

    /**
     * OLD STYLE:
     */
    /* 
    public function getTypeAttribute(){ return $this->name; }

    public function getStripePriceAttribute(){ return $this->stripe_plan; }

    public function setTypeAttribute($value){ $this->name=$value; }

    public function setStripePriceAttribute($value){ $this->stripe_plan=$value; }
    */
    
    // Define the mutators for the 'name'->'type','stripe_plan'->'stripe_price' attribute
    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['name'],
            set: fn ($value, $attributes) => $attributes['name'],
        );
    }

    protected function stripePrice(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['stripe_plan'],
            set: fn ($value, $attributes) => $attributes['stripe_plan'],
        );
    }

}