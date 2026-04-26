<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckPriceJob;
use App\Models\Ad;
use App\Models\Subscription;
use App\Services\PriceParserService;
use App\Notifications\PriceChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckPriceJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_it_updates_price_and_notifies_subscribers_when_price_changes()
    {
        $this->mock(PriceParserService::class, function ($mock) {
            $mock->shouldReceive('getPrice')->andReturn(900);
        });

        $ad = Ad::create(['url' => 'https://olx.ua/test', 'current_price' => 1000]);
        Subscription::create([
            'ad_id' => $ad->id,
            'email' => 'user@example.com',
            'email_verified_at' => now()
        ]);

        $job = new CheckPriceJob($ad);
        app()->call([$job, 'handle']);

        $this->assertEquals(900, $ad->fresh()->current_price);
        Notification::assertSentOnDemand(PriceChangedNotification::class);
    }

    public function test_it_updates_price_but_does_not_notify_if_it_is_the_first_price()
    {
        $this->mock(PriceParserService::class, function ($mock) {
            $mock->shouldReceive('getPrice')->andReturn(500);
        });

        $ad = Ad::create(['url' => 'https://olx.ua/new', 'current_price' => null]);

        Subscription::create([
            'ad_id' => $ad->id,
            'email' => 'user@example.com',
            'email_verified_at' => now()
        ]);

        $job = new CheckPriceJob($ad);
        app()->call([$job, 'handle']);

        $this->assertEquals(500, $ad->fresh()->current_price);
        Notification::assertNothingSent();
    }

    public function test_it_does_nothing_if_parser_returns_zero_or_negative()
    {
        $this->mock(PriceParserService::class, function ($mock) {
            $mock->shouldReceive('getPrice')->andReturn(0);
        });

        $ad = Ad::create(['url' => 'https://olx.ua/fail', 'current_price' => 1000]);

        $job = new CheckPriceJob($ad);
        app()->call([$job, 'handle']);

        $this->assertEquals(1000, $ad->fresh()->current_price);
        Notification::assertNothingSent();
    }
}
