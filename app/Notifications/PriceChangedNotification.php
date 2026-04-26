<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriceChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $url,
        protected float $newPrice
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('The ad price has changed!')
            ->line('We would like to inform you that the price of the product you are following has changed.')
            ->line("New price: **{$this->newPrice}** UAH.")
            ->action('View the ad', $this->url)
            ->line('Thank you for using our service!');
    }
}
