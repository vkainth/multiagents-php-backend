<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentPageView extends Model
{
    protected $fillable = ['agent_id', 'date', 'count'];

    protected function casts(): array
    {
        return [
            'date'  => 'date',
            'count' => 'integer',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
