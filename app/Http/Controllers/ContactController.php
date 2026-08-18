<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentSettings;
use App\Services\LeadPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    /**
     * POST /contact
     *
     * General contact form for agent white-label sites (domain-mode requests).
     * Saves an AgentLead record, sends email notification, and pushes to
     * Follow Up Boss / GoHighLevel when configured.
     *
     * Agent resolution order:
     *   1. agent_slug field in request body
     *   2. Host header matched against agent_settings.custom_domain
     */
    public function submit(Request $request): JsonResponse
    {
        $rateLimitKey = 'contact-web:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json(
                ['error' => 'Too many requests. Please try again in ' . ceil($seconds / 60) . ' minute(s).'],
                429
            );
        }
        RateLimiter::hit($rateLimitKey, 600);

        $data = $request->validate([
            'name'             => 'nullable|string|max:120',
            'first_name'       => 'nullable|string|max:60',
            'last_name'        => 'nullable|string|max:60',
            'email'            => 'required|email|max:180',
            'phone'            => 'nullable|string|max:40',
            'message'          => 'nullable|string|max:2000',
            'property_address' => 'nullable|string|max:300',
            'agent_slug'       => 'nullable|string|max:80',
            'source_url'       => 'nullable|string|max:500',
        ]);

        // Resolve agent from slug field, or fall back to domain matching.
        $agent = null;
        if (!empty($data['agent_slug'])) {
            $agent = Agent::with('settings')
                ->where('slug', $data['agent_slug'])
                ->where('status', 'active')
                ->first();
        }

        if (!$agent) {
            $host = strtolower(trim($request->getHost()));
            if (str_starts_with($host, 'www.')) $host = substr($host, 4);
            $settings = AgentSettings::where('custom_domain', $host)->first();
            if ($settings) {
                $agent = Agent::with('settings')
                    ->where('id', $settings->agent_id)
                    ->where('status', 'active')
                    ->first();
            }
        }

        if (!$agent) {
            return response()->json(['error' => 'Agent not found for this domain.'], 404);
        }

        $name = $data['name'] ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        $nameParts = explode(' ', trim($name), 2);
        $firstName = $data['first_name'] ?? ($nameParts[0] ?: null);
        $lastName  = $data['last_name']  ?? ($nameParts[1] ?? null);

        // Save lead row. A failed insert returns an explicit error — no silent discard.
        try {
            DB::table('agent_leads')->insert([
                'agent_id'         => $agent->id,
                'form_type'        => 'contact',
                'name'             => $name,
                'first_name'       => $firstName,
                'last_name'        => $lastName,
                'email'            => $data['email'],
                'phone'            => $data['phone'] ?? null,
                'message'          => $data['message'] ?? null,
                'property_address' => $data['property_address'] ?? null,
                'source_url'       => $data['source_url'] ?? $request->headers->get('referer'),
                'ip_hash'          => hash('sha256', $request->ip() ?? ''),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ContactController: agent_leads insert failed for agent ' . $agent->slug . ': ' . $e->getMessage());
            return response()->json(['error' => 'Could not save your message. Please try again.'], 500);
        }

        // Notification email.
        $notifyEmail = $agent->settings?->notification_email ?: $agent->email;
        if ($notifyEmail) {
            try {
                $body = "New Contact Form lead from " . ($agent->settings?->custom_domain ?? $agent->slug . '.pixilink.com') . "\n"
                    . str_repeat('-', 44) . "\n"
                    . "Name:     " . ($name ?: '—') . "\n"
                    . "Email:    " . $data['email'] . "\n"
                    . "Phone:    " . ($data['phone'] ?? '—') . "\n"
                    . "Property: " . ($data['property_address'] ?? '—') . "\n"
                    . "Message:  " . ($data['message'] ?? '—') . "\n"
                    . "Source:   " . ($data['source_url'] ?? $request->headers->get('referer') ?? '—') . "\n"
                    . str_repeat('-', 44) . "\n"
                    . "View leads: https://website.pixilink.com/admin/agents/{$agent->id}/leads\n";

                Mail::raw(
                    $body,
                    fn($m) => $m->to($notifyEmail)->subject("[Contact Form] New Lead — " . ($name ?: $data['email']))
                );
            } catch (\Throwable $mailErr) {
                Log::warning('ContactController: mail failed for agent ' . $agent->slug . ': ' . $mailErr->getMessage());
            }
        }

        // CRM push — failures are logged, never block response.
        $pipelineData = array_merge($data, [
            'name'      => $name,
            'form_type' => 'contact',
        ]);
        LeadPipeline::pushToFollowUpBoss($agent, $pipelineData);
        LeadPipeline::pushToGoHighLevel($agent, $pipelineData);
        LeadPipeline::pushToLofty($agent, $pipelineData);

        return response()->json(['success' => true]);
    }
}
