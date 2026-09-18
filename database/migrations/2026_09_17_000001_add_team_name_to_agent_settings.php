<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The name of the team an agent works within, e.g. "eXimus Team".
     *
     * Needed to say "Neb Yidegiligne from eXp Realty and the eXimus Team" and to
     * attribute long-tenure accolades to the TEAM rather than to the individual.
     * findfraservalleyhomes.com changed hands from Randy Dyck to Neb Yidegiligne
     * while keeping the team's 30-year record, Medallion Club run and award history
     * — those belong to eXimus, not to Neb personally, and printing them as his
     * would be a false credential claim.
     *
     * Distinct from `brokerage` (eXp Realty — the licensed firm) and from
     * `team_members` (the roster of individuals). varchar, not JSON: the MariaDB
     * replica has no native JSON type and a JSON column stops the replica with
     * Error 1677.
     */
    public function up(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('agent_settings', 'team_name')) {
                $table->string('team_name', 120)->nullable()
                    ->comment('Team the agent belongs to, e.g. "eXimus Team". Long-tenure accolades are attributed here, not to the individual.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agent_settings', function (Blueprint $table) {
            $table->dropColumn('team_name');
        });
    }
};
