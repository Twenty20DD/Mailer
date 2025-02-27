<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::post(
    config('mailer.webhook_url', '/mailer/webhook'),
    [WebhookController::class, 'handleWebhook']
)->name('mailer.webhook');
