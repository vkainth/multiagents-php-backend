<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentFeature;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FeatureFlagsController extends Controller
{
    public function index()
    {
        $agents   = Agent::with('features')->orderBy('name')->get();
        $features = AgentFeature::FEATURES;

        return view('admin.feature-flags.index', compact('agents', 'features'));
    }

    public function toggle(Request $request, Agent $agent)
    {
        $request->validate([
            'feature_key' => 'required|string|in:' . implode(',', array_keys(AgentFeature::FEATURES)),
        ]);

        $key     = $request->input('feature_key');
        $feature = AgentFeature::firstOrNew(['agent_id' => $agent->id, 'feature_key' => $key]);
        $feature->enabled = !$feature->enabled;
        $feature->save();

        Cache::forget("agent_features_{$agent->id}");

        AdminAuditLog::record('feature_toggled', $agent->id, [
            'feature' => $key,
            'enabled' => $feature->enabled,
        ]);

        return response()->json(['enabled' => $feature->enabled]);
    }
}
