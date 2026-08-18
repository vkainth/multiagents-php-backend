<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\AgentLeadNotification;
use App\Models\Agent;
use App\Models\AgentLead;
use App\Models\AgentLeadSmsLog;
use App\Helpers\AgentContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\JsonResponse;

/**
 * AgentLeadController
 *
 * Handles W1 / W2 / W3 lead form submissions from agent white-label sites.
 *
 * - Stores lead to agent_leads table
 * - Sends notification email to agent when email pref is on for that lead type
 * - Sends immediate SMS via Twilio when SMS pref is on for that lead type
 * - Pushes lead to Follow-Up Boss when fub_api_key is configured
 * - Returns JSON so the JS can show success/error in-page
 */
class AgentLeadController extends Controller
{
    /**
     * POST /agent/{agentSlug}/lead
     */
    public function store(Request $request, string $agentSlug): JsonResponse
    {
        $agent = AgentContext::current()
            ?? Agent::where('slug', $agentSlug)->where('status', 'active')->first();

        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'Agent not found.'], 404);
        }

        $validated = $request->validate([
            'form_type'        => 'required|in:w1,w2,w3',
            'name'             => 'required|string|max:120',
            'email'            => 'required|email|max:180',
            'phone'            => 'nullable|string|max:40',
            'message'          => 'nullable|string|max:2000',
            'property_address' => 'nullable|string|max:300',
            'property_type'    => 'nullable|string|max:80',
            'timeline'         => 'nullable|string|max:80',
            'budget'           => 'nullable|string|max:80',
            'preferred_date'   => 'nullable|date',
            'listing_slug'     => 'nullable|string|max:200',
        ]);

        // Store lead
        $lead = AgentLead::create([
            'agent_id'         => $agent->id,
            'form_type'        => $validated['form_type'],
            'name'             => $validated['name'],
            'email'            => $validated['email'],
            'phone'            => $validated['phone'] ?? null,
            'message'          => $validated['message'] ?? null,
            'property_address' => $validated['property_address'] ?? null,
            'property_type'    => $validated['property_type'] ?? null,
            'timeline'         => $validated['timeline'] ?? null,
            'budget'           => $validated['budget'] ?? null,
            'preferred_date'   => $validated['preferred_date'] ?? null,
            'listing_slug'     => $validated['listing_slug'] ?? null,
            'source_url'       => $request->headers->get('referer'),
            'ip_hash'          => hash('sha256', $request->ip()),
        ]);

        $settings  = $agent->settings;
        $formType  = $lead->form_type;

        // Send notification email (respects per-type email toggle)
        if ($settings?->getNotifPref($formType, 'email') ?? true) {
            $this->sendNotificationEmail($agent, $lead);
        }

        // Send immediate SMS (respects per-type SMS toggle — not gated on verification)
        if ($settings?->getNotifPref($formType, 'sms') ?? false) {
            $this->sendImmediateSms($agent, $lead);
        }

        // Push to Follow-Up Boss if configured
        $this->pushToFollowUpBoss($agent, $lead);

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }

    protected function sendNotificationEmail(Agent $agent, AgentLead $lead): void
    {
        $settings = $agent->settings;

        // Per-type email override takes precedence over lead_routing
        $toEmail = $settings?->getNotifEmailOverride($lead->form_type);

        if (!$toEmail) {
            $routing = $settings?->effectiveLeadRouting() ?? [];
            $toEmail = match ($lead->form_type) {
                'w1' => $routing['w1_email'] ?? null,
                'w2' => $routing['w2_email'] ?? null,
                'w3' => $routing['w3_email'] ?? null,
                default => null,
            } ?? $agent->email ?? null;
        }

        if (!$toEmail) {
            Log::warning("AgentLead #{$lead->id}: no notification email configured for agent {$agent->slug}");
            return;
        }

        try {
            Mail::to($toEmail)->send(new AgentLeadNotification($agent, $lead));
        } catch (\Throwable $e) {
            Log::error("AgentLead #{$lead->id} email failed: " . $e->getMessage());
        }
    }

    protected function sendImmediateSms(Agent $agent, AgentLead $lead): void
    {
        $settings = $agent->settings;
        $phone    = $settings?->notification_phone;

        if (!$phone || !config('services.twilio.sid') || !config('services.twilio.token')) {
            Log::warning("AgentLead #{$lead->id}: SMS pref on but no phone/Twilio config for agent {$agent->slug}");
            return;
        }

        // Rate-limit: one SMS per lead (shared log with verified job)
        if (AgentLeadSmsLog::where('agent_lead_id', $lead->id)->exists()) {
            return;
        }

        $formLabels = ['w1' => 'Showing Request', 'w2' => 'Home Evaluation', 'w3' => 'Mortgage Pre-Qual'];
        $typeLabel  = $formLabels[$lead->form_type] ?? 'Lead Inquiry';
        $body       = "New lead from {$lead->name} — {$typeLabel}. Reply STOP to opt out.";

        try {
            $twilio  = new \Twilio\Rest\Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $message = $twilio->messages->create($phone, [
                'from' => config('services.twilio.from'),
                'body' => $body,
            ]);

            AgentLeadSmsLog::create([
                'agent_lead_id' => $lead->id,
                'agent_id'      => $agent->id,
                'to_phone'      => $phone,
                'message'       => $body,
                'status'        => 'sent',
                'twilio_sid'    => $message->sid,
            ]);
        } catch (\Throwable $e) {
            Log::warning("AgentLead #{$lead->id}: immediate SMS failed: " . $e->getMessage());

            AgentLeadSmsLog::create([
                'agent_lead_id' => $lead->id,
                'agent_id'      => $agent->id,
                'to_phone'      => $phone,
                'message'       => $body,
                'status'        => 'failed',
            ]);
        }
    }

    /**
     * Push the lead to Follow-Up Boss via their Events API.
     * Only fires when agent_settings.fub_enabled is true and fub_api_key is set.
     *
     * @see https://followupboss.com/api2/docs
     */
    protected function pushToFollowUpBoss(Agent $agent, AgentLead $lead): void
    {
        $settings = $agent->settings;

        if (!$settings || empty($settings->fub_api_key)) {
            return;
        }

        $formLabels = [
            'w1' => 'Showing Request',
            'w2' => 'Home Evaluation',
            'w3' => 'Mortgage Pre-Qualification',
        ];

        $person = [
            'contacted'  => false,
            'firstName'  => $this->extractFirstName($lead->name),
            'lastName'   => $this->extractLastName($lead->name),
            'stage'      => 'Lead',
            'source'     => $agent->name . ' Website',
            'emails'     => [['value' => $lead->email]],
            'phones'     => $lead->phone ? [['value' => $lead->phone]] : [],
        ];

        if ($lead->source_url) {
            $person['sourceUrl'] = $lead->source_url;
        }

        $payload = [
            'source' => $agent->name . ' Website',
            'system' => 'website_api',
            'type'   => $formLabels[$lead->form_type] ?? 'General Inquiry',
            'person' => $person,
            'note'   => $this->buildFubMessage($lead) ?: null,
        ];

        if ($lead->property_address) {
            $payload['property'] = ['street' => $lead->property_address];
        }

        $payload = array_filter($payload, fn ($v) => $v !== null);

        try {
            $response = Http::withBasicAuth($settings->fub_api_key, '')
                ->timeout(8)
                ->post('https://api.followupboss.com/v1/events', $payload);

            if (!$response->successful()) {
                Log::warning("AgentLead #{$lead->id} FUB push failed [{$response->status()}]: " . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error("AgentLead #{$lead->id} FUB push error: " . $e->getMessage());
        }
    }

    private function extractFirstName(string $name): string
    {
        $parts = explode(' ', trim($name), 2);
        return $parts[0];
    }

    private function extractLastName(string $name): string
    {
        $parts = explode(' ', trim($name), 2);
        return $parts[1] ?? '';
    }

    private function buildFubMessage(AgentLead $lead): string
    {
        $lines = [];

        if ($lead->property_type) {
            $lines[] = 'Property type: ' . $lead->property_type;
        }
        if ($lead->timeline) {
            $lines[] = 'Timeline: ' . $lead->timeline;
        }
        if ($lead->budget) {
            $lines[] = 'Budget: ' . $lead->budget;
        }
        if ($lead->preferred_date) {
            $lines[] = 'Preferred date: ' . $lead->preferred_date;
        }
        if ($lead->listing_slug) {
            $lines[] = 'Listing: ' . url('/listing/' . $lead->listing_slug);
        }
        if ($lead->message) {
            $lines[] = $lead->message;
        }
        if ($lead->source_url) {
            $lines[] = 'Source: ' . $lead->source_url;
        }

        return implode("\n", $lines);
    }
}
