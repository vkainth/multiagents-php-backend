<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Redeems an admin impersonation token and returns the agent's portal session payload.
 *
 * Single use, enforced by an atomic conditional UPDATE rather than a read-then-write:
 * two simultaneous requests with the same token must not both succeed, and checking
 * consumed_at in PHP before writing it leaves exactly that race open.
 */
class ImpersonationConsumeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $raw = (string) $request->input('token');
        if ($raw === '') {
            return response()->json(['error' => 'Token required'], 422);
        }

        $hash = hash('sha256', $raw);

        // Claim it: only the request that flips consumed_at from NULL proceeds.
        $claimed = DB::table('admin_impersonations')
            ->where('token_hash', $hash)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->update(['consumed_at' => now(), 'updated_at' => now()]);

        if ($claimed !== 1) {
            return response()->json(['error' => 'This link has expired or was already used'], 401);
        }

        $row = DB::table('admin_impersonations')->where('token_hash', $hash)->first();

        $agent = Agent::with('settings')->find($row->agent_id);
        if (! $agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $adminEmail = DB::table('admins')->where('id', $row->admin_id)->value('email');

        Log::info('Admin impersonation consumed', [
            'admin' => $adminEmail,
            'agent' => $agent->slug,
        ]);

        return response()->json([
            'agent' => [
                'id'          => $agent->id,
                'name'        => $agent->name,
                'email'       => $agent->email,
                'slug'        => $agent->slug,
                'theme_color' => $agent->theme_color,
                'theme_slug'  => $agent->theme_slug,
                'domain'      => $agent->settings?->custom_domain,
            ],
            'impersonated_by' => $adminEmail,
        ]);
    }
}
