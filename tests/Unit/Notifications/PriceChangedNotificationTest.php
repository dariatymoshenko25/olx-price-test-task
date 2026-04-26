<?php

namespace Tests\Unit\Notifications;

use App\Notifications\PriceChangedNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class PriceChangedNotificationTest extends TestCase
{
    public function test_it_has_correct_mail_content()
    {
        $url = 'https://olx.ua/iphone-15';
        $price = 25000.50;

        $notification = new PriceChangedNotification($url, $price);

        $mail = $notification->toMail(null);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertEquals('The ad price has changed!', $mail->subject);
        $this->assertContains("New price: **25000.5** UAH.", $mail->introLines);
        $this->assertEquals('View the ad', $mail->actionText);
        $this->assertEquals($url, $mail->actionUrl);
        $this->assertContains('Thank you for using our service!', $mail->outroLines);
    }

    public function test_it_uses_mail_channel()
    {
        $notification = new PriceChangedNotification('https://olx.ua', 100);

        $this->assertEquals(['mail'], $notification->via(null));
    }
}
