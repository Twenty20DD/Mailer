<?php

use Illuminate\Support\Facades\Route;
use Twenty20\Mailer\Http\Controllers\WebhookController;

Route::post(
    config('mailer.webhook_url', '/mailer/webhook'),
    [WebhookController::class, 'handleWebhook']
)->name('mailer.webhook');
