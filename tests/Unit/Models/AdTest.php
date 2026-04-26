<?php

namespace Tests\Unit\Models;

use App\Models\Ad;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_many_subscriptions()
    {
        $ad = Ad::create([
            'url' => 'https://olx.ua/iphone',
            'current_price' => 20000.50
        ]);

        Subscription::create([
            'ad_id' => $ad->id,
            'email' => 'user1@test.com'
        ]);

        Subscription::create([
            'ad_id' => $ad->id,
            'email' => 'user2@test.com'
        ]);

        $this->assertCount(2, $ad->subscriptions);
        $this->assertInstanceOf(Subscription::class, $ad->subscriptions->first());
    }

    public function test_it_casts_current_price_to_float()
    {
        $ad = Ad::create([
            'url' => 'https://olx.ua/test',
            'current_price' => '1500.75'
        ]);

        $this->assertIsFloat($ad->fresh()->current_price);
        $this->assertEquals(1500.75, $ad->current_price);
    }

    public function test_it_has_correct_fillable_attributes()
    {
        $ad = new Ad();
        $this->assertEquals(['url', 'current_price'], $ad->getFillable());
    }
}
