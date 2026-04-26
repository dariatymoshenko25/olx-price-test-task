<?php

namespace Tests\Feature\Commands;

use App\Jobs\CheckPriceJob;
use App\Models\Ad;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchPriceChecksCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_jobs_for_verified_subscriptions_via_command()
    {
        Queue::fake();

        $verifiedAd = Ad::create(['url' => 'https://olx.ua/verified']);
        Subscription::create([
            'ad_id' => $verifiedAd->id,
            'email' => 'ok@test.com',
            'email_verified_at' => now()
        ]);

        $unverifiedAd = Ad::create(['url' => 'https://olx.ua/unverified']);
        Subscription::create([
            'ad_id' => $unverifiedAd->id,
            'email' => 'no@test.com',
            'email_verified_at' => null
        ]);

        $this->artisan('app:dispatch-price-checks-command')
            ->assertExitCode(0);

        Queue::assertPushed(CheckPriceJob::class, function ($job) use ($verifiedAd) {
            return $job->getAd()->id === $verifiedAd->id;
        });

        Queue::assertNotPushed(CheckPriceJob::class, function ($job) use ($unverifiedAd) {
            return $job->getAd()->id === $unverifiedAd->id;
        });
    }
}
