<?php

namespace tests\Feature\Services;

use App\Enums\SubscriptionStatus;
use App\Jobs\CheckPriceJob;
use App\Models\Ad;
use App\Models\Subscription;
use App\Notifications\VerifySubscriptionNotification;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;
    private SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SubscriptionService::class);
        Notification::fake();
        Queue::fake();
    }

    public function test_it_creates_new_subscription_and_triggers_price_check()
    {
        $url   = 'https://www.olx.ua/d/uk/obyavlenie/iphone-15-IDXXXX.html';
        $email = 'test@example.com';

        $status = $this->service->subscribe($url, $email);

        $this->assertEquals(SubscriptionStatus::Created, $status);

        $this->assertDatabaseHas('ads', ['url' => $url]);
        $this->assertDatabaseHas('subscriptions', ['email' => $email]);

        Queue::assertPushed(CheckPriceJob::class);
        Notification::assertSentOnDemand(
            VerifySubscriptionNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'test@example.com'
        );    }

    public function test_it_resends_verification_if_not_verified_yet()
    {
        $ad = Ad::create(['url' => 'https://olx.ua/ad1']);
        Subscription::create(['ad_id' => $ad->id, 'email' => 'user@example.com']);

        $status = $this->service->subscribe($ad->url, 'user@example.com');

        $this->assertEquals(SubscriptionStatus::Resent, $status);
        Notification::assertSentOnDemand(VerifySubscriptionNotification::class);
    }

    public function test_it_throws_exception_if_already_verified()
    {
        $ad = Ad::create(['url' => 'https://olx.ua/ad1']);
        Subscription::create([
            'ad_id'             => $ad->id,
            'email'             => 'user@example.com',
            'email_verified_at' => now()
        ]);

        $this->expectException(ValidationException::class);

        $this->service->subscribe($ad->url, 'user@example.com');
    }

    public function test_it_marks_subscription_as_verified()
    {
        $sub = Subscription::create([
            'ad_id' => Ad::create(['url' => 'https://olx.ua/1'])->id,
            'email' => 'test@test.com'
        ]);

        $this->assertNull($sub->email_verified_at);

        $this->service->verify($sub);

        $this->assertNotNull($sub->fresh()->email_verified_at);
    }
}
