<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestShowing extends Mailable
{
    use Queueable, SerializesModels;

    public string $rawData;

    public function __construct(string $rawData)
    {
        $this->rawData = $rawData;
    }

    public function build()
    {
        return $this->subject('New Showing Request – BC Condos And Homes')
                    ->text('emails.plain_notification')
                    ->with(['body' => $this->rawData]);
    }
}
