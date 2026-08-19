<?php

namespace App\Jobs;

use App\Models\AgentLead;
use App\Models\AgentLeadSmsLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AgentLeadVerifiedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly AgentLead $lead)
    {
    }

    public function handle(): void
    {
        $lead  = $this->lead->loadMissing('agent.settings');
        $agent = $lead->agent;

        if (!$agent) {
            return;
        }

        $settings = $agent->settings;
        $formType = $lead->form_type;

        // Push the now-verified lead to the agent's CRM. Upserts rather than creating,
        // because the lead was already pushed at account creation - before OTP - so a
        // plain create would give the agent two records for one person. Wrapped so a CRM
        // outage cannot stop the agent's notification email going out below.
        try {
            \App\Services\LeadPipeline::pushVerifiedLead($agent, [
                'first_name' => $lead->first_name,
                'last_name'  => $lead->last_name,
                'name'       => $lead->name,
                'email'      => $lead->email,
                'phone'      => $lead->phone,
                'form_type'  => $formType,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('verified CRM push failed lead=' . $lead->id . ': ' . $e->getMessage());
        }

        // Rate-limit: one SMS per lead (shared with immediate-send in AgentLeadController)
        $alreadySent = AgentLeadSmsLog::where('agent_lead_id', $lead->id)->exists();

        $smsEnabled   = $settings?->getNotifPref($formType, 'sms')   ?? false;
        $emailEnabled = $settings?->getNotifPref($formType, 'email') ?? true;

        // ── SMS ─────────────────────────────────────────────────────────────
        // Send verified-lead SMS only when the pref is on and SMS hasn't fired yet
        // (immediate submission send is preferred; verified send is a safety net).
        $smsSent = false;

        if ($smsEnabled && !$alreadySent) {
            $phone = $settings?->notification_phone;

            $addressOrGeneral = $lead->listing_id
                ? "Listing #{$lead->listing_id}"
                : 'general inquiry';

            $body = "New verified lead from {$lead->first_name} — {$lead->formTypeLabel()} — {$addressOrGeneral}. Reply STOP to opt out.";

            if ($phone && config('services.twilio.sid') && config('services.twilio.token')) {
                try {
                    $twilio = new \Twilio\Rest\Client(
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

                    $smsSent = true;
                } catch (\Throwable $e) {
                    Log::warning("AgentLeadVerifiedJob: Twilio SMS failed for lead {$lead->id}: " . $e->getMessage());

                    AgentLeadSmsLog::create([
                        'agent_lead_id' => $lead->id,
                        'agent_id'      => $agent->id,
                        'to_phone'      => $phone ?? '',
                        'message'       => $body,
                        'status'        => 'failed',
                    ]);
                }
            }
        }

        // ── Email ───────────────────────────────────────────────────────────
        // Send when the email pref is on and no SMS went out for this lead.
        //
        // Previously this was `$smsEnabled && !$smsSent` - i.e. email only as a
        // fallback for a FAILED SMS. Every agent currently has sms=false / email=true,
        // so that condition was never true and no agent has ever received a
        // verified-lead email. Email is the reliable channel here (Twilio SMS is
        // broken platform-wide: config('services.twilio.from') is undefined), so an
        // agent who has asked for email notifications should get one.
        //
        // !$smsSent still prevents double-notifying when SMS did deliver.
        $needsEmail = $emailEnabled && !$smsSent;

        if ($needsEmail) {
            $notifyEmail = $settings?->getNotifEmailOverride($formType)
                        ?? $settings?->notification_email
                        ?? $agent->email;

            $addressOrGeneral = $lead->listing_id
                ? "Listing #{$lead->listing_id}"
                : 'general inquiry';

            if ($notifyEmail) {
                try {
                    Mail::raw(
                        "New verified lead:\n\n" .
                        "Name: {$lead->first_name} {$lead->last_name}\n" .
                        "Email: {$lead->email}\n" .
                        "Phone: {$lead->phone}\n" .
                        "Type: {$lead->formTypeLabel()}\n" .
                        "Listing: {$addressOrGeneral}\n" .
                        "Page: {$lead->source_url}\n",
                        function ($m) use ($notifyEmail, $lead) {
                            $m->to($notifyEmail)
                              ->subject("New Verified Lead — {$lead->first_name} {$lead->last_name}");
                        }
                    );
                } catch (\Throwable $e) {
                    Log::warning("AgentLeadVerifiedJob: Email fallback failed for lead {$lead->id}: " . $e->getMessage());
                }
            }
        }
    }
}
