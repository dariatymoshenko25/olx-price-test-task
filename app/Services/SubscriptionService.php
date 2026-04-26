<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Jobs\CheckPriceJob;
use App\Models\Ad;
use App\Models\Subscription;
use App\Notifications\PriceChangedNotification;
use Illuminate\Support\Facades\URL;
use App\Notifications\VerifySubscriptionNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function subscribe(string $url, string $email): SubscriptionStatus
    {
        $ad = Ad::firstOrCreate(['url' => $url]);

        if (is_null($ad->current_price)) {
            CheckPriceJob::dispatch($ad);
        }

        $subscription = Subscription::where('ad_id', $ad->id)
            ->where('email', $email)
            ->first();

        if ($subscription) {
            if ($subscription->email_verified_at) {
                throw ValidationException::withMessages([
                    'email' => ['You are already subscribed to this ad.']
                ]);
            }

            $this->sendVerificationNotification($subscription);
            return SubscriptionStatus::Resent;
        }

        $subscription = Subscription::create([
            'ad_id' => $ad->id,
            'email' => $email,
        ]);

        $this->sendVerificationNotification($subscription);
        return SubscriptionStatus::Created;
    }

    private function sendVerificationNotification(Subscription $subscription): void
    {
        $verifyUrl = URL::temporarySignedRoute(
            'subscription.verify',
            now()->addDays(1),
            ['subscription' => $subscription->id]
        );

        Notification::route('mail', $subscription->email)
            ->notify(new VerifySubscriptionNotification($verifyUrl));
    }

    public function verify(Subscription $subscription): void
    {
        if (is_null($subscription->email_verified_at)) {
            $subscription->update([
                'email_verified_at' => now(),
            ]);
        }
    }

    public function dispatchPriceChecks(): void
    {
        Ad::whereHas('subscriptions', fn($q) => $q->whereNotNull('email_verified_at'))
            ->select(['id', 'url'])
            ->chunkById(500, function ($ads) {
                foreach ($ads as $ad) {
                    CheckPriceJob::dispatch($ad);
                }
            });
    }

    public function notifyAdSubscribers(Ad $ad, float $newPrice): void
    {
        $ad->subscriptions()
            ->whereNotNull('email_verified_at')
            ->select(['id', 'email', 'ad_id'])
            ->chunkById(200, function ($subscriptions) use ($ad, $newPrice) {
                foreach ($subscriptions as $subscription) {
                    Notification::route('mail', $subscription->email)
                        ->notify(new PriceChangedNotification($ad->url, $newPrice));
                }
            });
    }
}
