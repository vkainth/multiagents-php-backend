<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * "View this agent's portal as them", from the admin agents list.
 *
 * The handoff is a single-use token rather than a shared-secret JWT: the database
 * enforces one-time use, the token is opaque, and there is no cross-language crypto to
 * get subtly wrong. It lives for 60 seconds — long enough for a redirect, short enough
 * that a token left in browser history or a Referer header is already dead.
 *
 * The resulting portal session is marked as impersonated and is READ-ONLY. Being able to
 * look at an agent's portal is support; being able to silently change their profile,
 * their integrations or their saved card is not, and an admin acting invisibly as a
 * customer is exactly the thing that makes an audit log meaningless.
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, Agent $agent)
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin) {
            abort(403);
        }

        $agent->loadMissing('settings');

        $domain = $agent->settings?->custom_domain;
        $base   = $domain
            ? 'https://' . $domain
            : rtrim((string) config('app.url'), '/');

        $raw = Str::random(64);

        DB::table('admin_impersonations')->insert([
            'admin_id'   => $admin->id,
            'agent_id'   => $agent->id,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => now()->addSeconds(60),
            'ip_hash'    => hash('sha256', (string) ($request->header('CF-Connecting-IP') ?: $request->ip())),
            'user_agent' => Str::limit((string) $request->userAgent(), 240, ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Admin impersonation started', [
            'admin' => $admin->email,
            'agent' => $agent->slug,
        ]);

        return redirect()->away($base . '/agent-portal/impersonate?t=' . urlencode($raw));
    }

    /** Recent impersonations, so the audit trail is visible rather than buried in a table. */
    public function log()
    {
        $rows = DB::table('admin_impersonations as i')
            ->leftJoin('admins as ad', 'ad.id', '=', 'i.admin_id')
            ->leftJoin('agents as ag', 'ag.id', '=', 'i.agent_id')
            ->orderByDesc('i.created_at')
            ->limit(100)
            ->get([
                'i.created_at', 'i.consumed_at', 'ad.email as admin_email',
                'ag.name as agent_name', 'ag.slug as agent_slug',
            ]);

        return view('admin.impersonations.index', ['rows' => $rows]);
    }
}
