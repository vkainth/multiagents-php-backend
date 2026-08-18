<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'admin_audit_log';

    protected $fillable = ['admin_id', 'action', 'target_agent_id', 'details'];

    protected function casts(): array
    {
        return [
            'details'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'target_agent_id');
    }

    public static function record(string $action, ?int $agentId = null, array $details = []): void
    {
        $adminId = auth('admin')->id();

        static::create([
            'admin_id'        => $adminId,
            'action'          => $action,
            'target_agent_id' => $agentId,
            'details'         => $details ?: null,
        ]);
    }
}
