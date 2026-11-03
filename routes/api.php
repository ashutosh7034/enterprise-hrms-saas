<?php

use App\Http\Controllers\BiometricWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/biometric/webhook', [BiometricWebhookController::class, 'ingest'])->name('api.biometric.webhook');

