<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    public function index()
    {
        $agents = $this->activeAgentsWithDomains();

        $latestRuns = [];
        foreach ($agents as $agent) {
            $latestRuns[$agent->domain] = DB::table('cache_warmup_runs')
                ->where('domain', $agent->domain)
                ->orderByDesc('id')
                ->first();
        }

        $urlCounts = [];
        foreach ($agents as $agent) {
            $urlCounts[$agent->domain] = $this->fetchSitemapCount($agent->domain);
        }

        return view('admin.sitemaps.index', compact('agents', 'latestRuns', 'urlCounts'));
    }

    public function start(Request $request, string $domain): JsonResponse
    {
        $domain = strtolower(trim($domain));

        // Only allow known, active agent domains — prevents SSRF via arbitrary domains
        $knownDomains = $this->activeAgentsWithDomains()->pluck('domain')->map('strtolower')->all();
        if (!in_array($domain, $knownDomains, true)) {
            return response()->json(['error' => 'Unknown or inactive domain'], 403);
        }

        $runId = DB::table('cache_warmup_runs')->insertGetId([
            'domain'      => $domain,
            'status'      => 'pending',
            'total_urls'  => 0,
            'warmed_urls' => 0,
            'error_count' => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // PHP_BINARY resolves the running interpreter; fall back to cPanel PHP on this server
        $php     = PHP_BINARY ?: '/opt/cpanel/ea-php83/root/usr/bin/php';
        $artisan = base_path('artisan');
        $logFile = storage_path('logs/warmup-' . preg_replace('/[^a-z0-9\-.]/', '-', $domain) . '.log');
        $cmd     = implode(' ', [
            escapeshellarg($php),
            escapeshellarg($artisan),
            'warmup:cache',
            escapeshellarg($domain),
            (int) $runId,
        ]);
        // The trailing & inside the bash -c string backgrounds the nohup process;
        // bash exits immediately so proc_close returns without blocking.
        // setsid creates a new session — the child survives HTTP request teardown.
        $fullCmd = 'nohup ' . $cmd . ' > ' . escapeshellarg($logFile) . ' 2>&1 &';

        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open('setsid bash -c ' . escapeshellarg($fullCmd), $desc, $pipes);
        if (!is_resource($proc)) {
            DB::table('cache_warmup_runs')->where('id', $runId)
                ->update(['status' => 'failed', 'finished_at' => now()]);
            return response()->json(['error' => 'Failed to launch warm-up process'], 500);
        }
        proc_close($proc);

        return response()->json(['run_id' => $runId, 'status' => 'pending']);
    }

    public function status(int $run): JsonResponse
    {
        $row = DB::table('cache_warmup_runs')->find($run);
        if (!$row) {
            return response()->json(['error' => 'Run not found'], 404);
        }

        return response()->json([
            'status'      => $row->status,
            'total_urls'  => (int) $row->total_urls,
            'warmed_urls' => (int) $row->warmed_urls,
            'current_url' => $row->current_url,
            'error_count' => (int) $row->error_count,
            'started_at'  => $row->started_at,
            'finished_at' => $row->finished_at,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function activeAgentsWithDomains()
    {
        return DB::table('agent_settings')
            ->join('agents', 'agents.id', '=', 'agent_settings.agent_id')
            ->whereNotNull('agent_settings.custom_domain')
            ->where('agent_settings.custom_domain', '!=', '')
            ->where('agents.status', 'active')
            ->select('agents.name', 'agent_settings.custom_domain as domain')
            ->orderBy('agents.name')
            ->get();
    }

    private function fetchSitemapCount(string $domain): ?int
    {
        $url = 'https://' . $domain . '/sitemap.xml';
        try {
            $ctx = stream_context_create([
                'http' => ['timeout' => 6, 'ignore_errors' => true],
                'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $xml = @file_get_contents($url, false, $ctx);
            if (!$xml) return null;
            preg_match_all('/<loc>/i', $xml, $m);
            return count($m[0]);
        } catch (\Throwable) {
            return null;
        }
    }
}
