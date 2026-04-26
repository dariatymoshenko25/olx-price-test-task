<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Ad;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use App\Notifications\VerifySubscriptionNotification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Queue::fake();
    }

    public function test_it_subscribes_and_returns_201_with_real_service_logic()
    {
        $payload = [
            'url' => 'https://www.olx.ua/d/uk/obyavlenie/iphone-test.html',
            'email' => 'user@example.com',
        ];

        $response = $this->postJson('/api/subscribe', $payload);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Created']);

        $this->assertDatabaseHas('ads', ['url' => $payload['url']]);
        $this->assertDatabaseHas('subscriptions', ['email' => $payload['email']]);

        Notification::assertSentOnDemand(VerifySubscriptionNotification::class);
    }

    public function test_it_returns_200_if_already_exists_but_not_verified()
    {
        $ad = Ad::create(['url' => 'https://olx.ua/test']);
        Subscription::create(['ad_id' => $ad->id, 'email' => 'user@example.com']);

        $response = $this->postJson('/api/subscribe', [
            'url' => 'https://olx.ua/test',
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Resent']);

        Notification::assertSentOnDemand(VerifySubscriptionNotification::class);
    }

    public function test_it_successfully_verifies_email()
    {
        $ad = Ad::create(['url' => 'https://olx.ua/verify-test']);
        $sub = Subscription::create([
            'ad_id' => $ad->id,
            'email' => 'verify@test.com'
        ]);

        $this->assertNull($sub->email_verified_at);

        $url = URL::temporarySignedRoute(
            'subscription.verify',
            now()->addMinutes(30),
            ['subscription' => $sub->id]
        );

        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Email successfully confirmed!']);

        $this->assertNotNull($sub->fresh()->email_verified_at);
    }

    public function test_subscribe_fails_if_already_verified()
    {
        $ad = Ad::create(['url' => 'https://olx.ua/iphone-15']);
        Subscription::create([
            'ad_id'             => $ad->id,
            'email'             => 'user@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/subscribe', [
            'url'   => 'https://olx.ua/iphone-15',
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'You are already subscribed to this ad.',
                'errors' => [
                    'email' => [
                        'You are already subscribed to this ad.'
                    ]
                ]
            ]);

        Notification::assertNothingSent();
    }

    public function test_it_validates_olx_url_and_email_format()
    {
        $response = $this->postJson('/api/subscribe', [
            'url'   => 'https://test/ad123',
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url', 'email']);
    }
}
