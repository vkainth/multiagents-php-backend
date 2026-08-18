<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $verifyUrl,
        public string $agentName = '',
        public string $agentBrokerage = '',
        public string $accentColor = '#c9a96e',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your email address');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verify-email');
    }
}
