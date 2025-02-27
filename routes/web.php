<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::post('mailer/webhook/', [WebhookController::class, 'handleWebhook'])->name('mailer.webhook');
