<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AgentWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $temporaryPassword,
        protected string $portalUrl
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to BC Condos & Homes — Your Agent Portal is Ready')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('Your agent portal has been set up. Use the credentials below to sign in.')
            ->line('**Portal URL:** ' . $this->portalUrl)
            ->line('**Email:** ' . $notifiable->email)
            ->line('**Temporary Password:** ' . $this->temporaryPassword)
            ->action('Sign In to Your Portal', $this->portalUrl)
            ->line('Please change your password after your first sign-in.')
            ->line('**Setup checklist:**')
            ->line('1. Upload your headshot and write a short bio')
            ->line('2. Pin up to 6 featured listings on your homepage')
            ->line('3. Add or verify your social media links')
            ->line('4. Review your notification email and phone number')
            ->salutation('— BC Condos & Homes Team');
    }
}
