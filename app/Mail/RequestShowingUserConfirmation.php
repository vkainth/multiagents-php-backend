<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestShowingUserConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Your Showing Request – BC Condos And Homes')
                    ->text('emails.plain_notification')
                    ->with(['body' => implode("\n", array_map(
                        fn($k, $v) => "$k: $v",
                        array_keys($this->data),
                        array_values($this->data)
                    ))]);
    }
}
