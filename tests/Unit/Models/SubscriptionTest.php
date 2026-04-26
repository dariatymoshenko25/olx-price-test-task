<?php

namespace Tests\Unit\Models;

use App\Models\Ad;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_belongs_to_an_ad()
    {
        $ad = Ad::create([
            'url' => 'https://olx.ua/test',
            'current_price' => 1000
        ]);

        $subscription = Subscription::create([
            'ad_id' => $ad->id,
            'email' => 'test@example.com',
            'email_verified_at' => now()
        ]);

        $this->assertInstanceOf(Ad::class, $subscription->ad);
        $this->assertEquals($ad->id, $subscription->ad->id);
    }

    public function test_it_has_correct_fillable_attributes()
    {
        $subscription = new Subscription();

        $expectedFillable = ['ad_id', 'email', 'email_verified_at'];

        $this->assertEquals($expectedFillable, $subscription->getFillable());
    }
}
