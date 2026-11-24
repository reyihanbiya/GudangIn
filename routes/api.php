<?php

use App\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/xendit/webhook', XenditWebhookController::class)
    ->name('xendit.webhook');
