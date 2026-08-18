<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AgentSettings::$fillable, AdminInternalController::agentUpdate()'s
     * $settingsKeys, and the admin AgentEditorForm payload all carry
     * `achievements` / `co_agent_achievements`, but the columns were never
     * created. Every save from /admin/agents/{id}/manage/settings therefore
     * died with SQLSTATE[42S22] 1054 Unknown column 'achievements'.
     *
     * longtext + nullable and no model cast, matching hero_stats and
     * team_members: the form sends these as already-JSON-encoded strings.
     */
    public function up(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('agent_settings', 'achievements')) {
                $table->longText('achievements')->nullable()
                    ->comment('JSON array of {label} badges shown on the agent hero');
            }
            if (!Schema::hasColumn('agent_settings', 'co_agent_achievements')) {
                $table->longText('co_agent_achievements')->nullable()
                    ->comment('JSON object keyed by co-agent name => array of {label}');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            $table->dropColumn(['achievements', 'co_agent_achievements']);
        });
    }
};
