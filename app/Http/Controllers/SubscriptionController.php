<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Http\Requests\SubscribeRequest;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $service
    ) {}

    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $status = $this->service->subscribe(
            $request->validated('url'),
            $request->validated('email')
        );

        return match($status) {
            SubscriptionStatus::Created => response()->json(['message' => 'Created'], 201),
            SubscriptionStatus::Resent => response()->json(['message' => 'Resent'], 200),
        };
    }

    public function verify(Subscription $subscription): JsonResponse
    {
        $this->service->verify($subscription);

        return response()->json(['message' => 'Email successfully confirmed!']);
    }
}
