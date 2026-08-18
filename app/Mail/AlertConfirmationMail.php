<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $confirmUrl;
    public string $contextTitle;

    public function __construct(string $confirmUrl, string $contextTitle = 'your alert')
    {
        $this->confirmUrl   = $confirmUrl;
        $this->contextTitle = $contextTitle;
    }

    public function build(): self
    {
        return $this->subject('Confirm your listing alert — BC Condos And Homes')
                    ->view('emails.alert_confirmation');
    }
}
