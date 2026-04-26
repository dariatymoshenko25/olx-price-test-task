<?php

namespace App\Jobs;

use App\Models\Ad;
use App\Services\PriceParserService;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckPriceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Ad $ad) {}

    public function handle(PriceParserService $parser, SubscriptionService $subService): void
    {
        $newPrice = $parser->getPrice($this->ad->url);

        if ($newPrice <= 0) return;

        $oldPrice = $this->ad->current_price;
        $this->ad->update(['current_price' => $newPrice]);

        if (!is_null($oldPrice) && $newPrice != $oldPrice) {
            $subService->notifyAdSubscribers($this->ad, $newPrice);
        }
    }

    public function getAd(): Ad
    {
        return $this->ad;
    }
}
