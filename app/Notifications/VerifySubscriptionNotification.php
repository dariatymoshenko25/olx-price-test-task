<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifySubscriptionNotification extends Notification
{
    public function __construct(protected string $verifyUrl) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your subscription')
            ->line('You have subscribed to the price change of your OLX ad.')
            ->action('Confirm Email', $this->verifyUrl)
            ->line('If you have not done so, just ignore this email. ');
    }
}
