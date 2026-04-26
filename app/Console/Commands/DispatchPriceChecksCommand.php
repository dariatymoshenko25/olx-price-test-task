<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class DispatchPriceChecksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-price-checks-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch price check jobs for all active subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionService $service): int
    {
        $service->dispatchPriceChecks();
        return self::SUCCESS;
    }
}
