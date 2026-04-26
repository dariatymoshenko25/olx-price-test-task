<?php

namespace Tests\Unit\Notifications;

use App\Notifications\VerifySubscriptionNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class VerifySubscriptionNotificationTest extends TestCase
{
    public function test_it_has_correct_verification_mail_content()
    {
        $testUrl = 'https://your-site.test/api/verify/1?signature=secret-hash';

        $notification = new VerifySubscriptionNotification($testUrl);

        $mail = $notification->toMail(null);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertEquals('Confirm your subscription', $mail->subject);
        $this->assertEquals('Confirm Email', $mail->actionText);
        $this->assertEquals($testUrl, $mail->actionUrl);
        $this->assertContains(
            'You have subscribed to the price change of your OLX ad.',
            $mail->introLines
        );
        $this->assertContains(
            'If you have not done so, just ignore this email.',
            $mail->outroLines
        );
    }

    public function test_it_uses_mail_channel()
    {
        $notification = new VerifySubscriptionNotification('http://localhost');

        $this->assertEquals(['mail'], $notification->via(null));
    }
}
