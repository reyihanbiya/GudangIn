<?php

use App\Http\Controllers\XenditWebhookController;
use App\Http\Controllers\MidtransWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/xendit/webhook', XenditWebhookController::class)
    ->name('xendit.webhook');

Route::post('/midtrans/notification', MidtransWebhookController::class)
    ->name('midtrans.notification');
