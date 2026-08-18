<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentMlsId extends Model
{
    public $timestamps = false;

    protected $fillable = ['agent_id', 'mls_id'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
