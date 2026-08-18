<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertHistory extends Model
{
    protected $table = 'alert_history';

    protected $fillable = [
        'userid', 'email', 'type', 'record_id', 'listing_ids', 'sent_at', 'created_at',
    ];

    protected $casts = [
        'listing_ids' => 'array',
        'sent_at'     => 'datetime',
        'created_at'  => 'datetime',
    ];

    public $timestamps = false;
}
