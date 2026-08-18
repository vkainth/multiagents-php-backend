<?php

namespace App\Mail;

use App\Models\Agent;
use App\Models\AgentLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgentLeadNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Agent $agent;
    public AgentLead $lead;

    public function __construct(Agent $agent, AgentLead $lead)
    {
        $this->agent = $agent;
        $this->lead  = $lead;
    }

    public function build(): static
    {
        $formLabels = [
            'w1' => 'Showing Request',
            'w2' => 'Home Evaluation',
            'w3' => 'Mortgage Pre-Qual',
        ];

        $formLabel = $formLabels[$this->lead->form_type] ?? 'Lead Inquiry';
        $subject   = '[Lead] ' . $formLabel . ' from ' . $this->lead->name;

        return $this->subject($subject)
                    ->replyTo($this->lead->email, $this->lead->name)
                    ->view('emails.agent_lead_notification')
                    ->with([
                        'agent'     => $this->agent,
                        'lead'      => $this->lead,
                        'formLabel' => $formLabel,
                    ]);
    }
}
