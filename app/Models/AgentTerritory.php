<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTerritory extends Model
{
    public $timestamps = false;

    protected $fillable = ['agent_id', 'city', 'subarea', 'board'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
