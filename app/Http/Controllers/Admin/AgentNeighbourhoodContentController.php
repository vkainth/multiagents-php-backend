<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\NeighbourhoodContent;
use App\Services\ArticleGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentNeighbourhoodContentController extends Controller
{
    public function index(Request $request)
    {
        $agents     = Agent::orderBy('name')->get();
        $selectedId = (int) $request->query('agent_id', $agents->first()->id ?? 0);
        $agent      = $agents->firstWhere('id', $selectedId) ?? $agents->first();

        $subareas = [];
        $content  = [];

        if ($agent) {
            $agent->loadMissing('territories');
            $subareas = $agent->territories
                ->pluck('subarea')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            if (empty($subareas)) {
                $subareas = $agent->territories
                    ->pluck('city')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();
            }

            $rows = NeighbourhoodContent::where('agent_id', $agent->id)->get()->keyBy('subarea');
            foreach ($subareas as $sub) {
                $content[$sub] = $rows->get($sub);
            }
        }

        return view('admin.neighbourhood_content.index', compact('agents', 'agent', 'subareas', 'content'));
    }

    public function generateLifestyle(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'subarea'  => 'required|string|max:150',
        ]);

        $agent   = Agent::findOrFail($request->input('agent_id'));
        $subarea = $request->input('subarea');
        $service = new ArticleGeneratorService($agent);

        try {
            $body = $service->generateNeighbourhoodLifestyle($subarea);
            if ($body) {
                NeighbourhoodContent::upsertLifestyle($agent->id, $subarea, $body);
                return redirect()
                    ->route('admin.neighbourhood-content.index', ['agent_id' => $agent->id])
                    ->with('success', "Lifestyle narrative regenerated for \"{$subarea}\".");
            }
        } catch (\Throwable $e) {
            Log::channel('daily')->error("Admin lifestyle regen failed for {$agent->slug}/{$subarea}: " . $e->getMessage());
        }

        return redirect()
            ->route('admin.neighbourhood-content.index', ['agent_id' => $agent->id])
            ->with('error', "Could not generate lifestyle narrative for \"{$subarea}\". Check the OpenAI API key and logs.");
    }

    public function generatePulse(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'subarea'  => 'required|string|max:150',
        ]);

        $agent   = Agent::findOrFail($request->input('agent_id'));
        $subarea = $request->input('subarea');
        $service = new ArticleGeneratorService($agent);

        try {
            $body = $service->generateWeeklyPulse($subarea);
            if ($body) {
                NeighbourhoodContent::upsertPulse($agent->id, $subarea, $body);
                return redirect()
                    ->route('admin.neighbourhood-content.index', ['agent_id' => $agent->id])
                    ->with('success', "Weekly pulse regenerated for \"{$subarea}\".");
            }
        } catch (\Throwable $e) {
            Log::channel('daily')->error("Admin pulse regen failed for {$agent->slug}/{$subarea}: " . $e->getMessage());
        }

        return redirect()
            ->route('admin.neighbourhood-content.index', ['agent_id' => $agent->id])
            ->with('error', "Could not generate pulse for \"{$subarea}\". No sold data this week or OpenAI error.");
    }
}
