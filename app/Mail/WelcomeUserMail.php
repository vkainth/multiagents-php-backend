<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName = '',
        public string $agentName = '',
        public string $agentBrokerage = '',
        public string $accentColor = '#c9a96e',
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->agentName
            ? "Welcome to {$this->agentName}'s site"
            : 'Welcome — you\'re all set';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.welcome-user');
    }
}
