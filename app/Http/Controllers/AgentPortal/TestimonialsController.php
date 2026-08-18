<?php

namespace App\Http\Controllers\AgentPortal;

use App\Console\Commands\ImportAgentTestimonialsCommand;
use App\Http\Controllers\Controller;
use App\Models\AgentTestimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TestimonialsController extends Controller
{
    public function index()
    {
        $agent    = Auth::guard('agent')->user()->load('settings');
        $settings = $agent->settings;

        $count = AgentTestimonial::where('agent_id', $agent->id)->count();

        // First-load: if no testimonials yet and a Google Place ID is configured,
        // attempt a synchronous import now (idempotent via external_id dedup).
        $importMessage = null;
        if ($count === 0 && $settings && !empty($settings->google_place_id)) {
            try {
                Artisan::call('agent:import-testimonials', [
                    '--agent'  => $agent->slug,
                    '--source' => 'google',
                ]);
                $count = AgentTestimonial::where('agent_id', $agent->id)->count();
                if ($count > 0) {
                    $importMessage = "Imported {$count} reviews from Google on first load.";
                }
            } catch (\Throwable $e) {
                Log::warning("TestimonialsController: first-load import failed for agent {$agent->id}: " . $e->getMessage());
            }
        }

        $testimonials = AgentTestimonial::where('agent_id', $agent->id)
            ->orderByDesc('date')
            ->paginate(20);

        return view('agent-portal.testimonials', compact('agent', 'testimonials', 'importMessage'));
    }

    public function toggleVisible(Request $request, AgentTestimonial $testimonial)
    {
        $agent = Auth::guard('agent')->user();

        if ($testimonial->agent_id !== $agent->id) {
            abort(403);
        }

        $testimonial->update(['visible' => !$testimonial->visible]);

        return response()->json([
            'visible' => $testimonial->visible,
            'message' => $testimonial->visible ? 'Review is now visible.' : 'Review hidden from your site.',
        ]);
    }
}
