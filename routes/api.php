<?php

use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
Route::get('/verify/{subscription}', [SubscriptionController::class, 'verify'])
    ->name('subscription.verify')
    ->middleware('signed');
