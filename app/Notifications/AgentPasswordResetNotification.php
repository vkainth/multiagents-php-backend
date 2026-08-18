<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AgentPasswordResetNotification extends ResetPassword
{
    /**
     * Override the reset URL to point at the agent portal's own reset route,
     * not the default Laravel /password/reset endpoint.
     */
    protected function resetUrl($notifiable): string
    {
        return route('agent-portal.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset Your Agent Portal Password')
            ->line('You are receiving this email because we received a password reset request for your Agent Portal account.')
            ->action('Reset Password', $this->resetUrl($notifiable))
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
