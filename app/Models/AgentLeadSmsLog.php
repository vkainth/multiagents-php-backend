<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentLeadSmsLog extends Model
{
    protected $table = 'agent_lead_sms_log';

    protected $fillable = [
        'agent_lead_id', 'agent_id', 'to_phone', 'message', 'status', 'twilio_sid',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(AgentLead::class, 'agent_lead_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
