<?php

use App\Http\Controllers\Api\NTAuthController;
use App\Http\Controllers\Api\NTIntegrationController;
use App\Http\Controllers\Api\NTUserController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\MaxBotWebhookController;
use App\Http\Middleware\MaxBotApiSecretValidation;
use App\Http\Middleware\MaxInitDataValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('nt')->group(function () {
    Route::post('/send-code', [NTAuthController::class, 'sendCode'])
        ->middleware(MaxInitDataValidation::class);
    Route::post('/verify-code', [NTAuthController::class, 'verifyCode']);
    Route::post('/register', [NTAuthController::class, 'register'])
        ->middleware(MaxInitDataValidation::class);

    Route::prefix('me')->middleware('auth:sanctum')
        ->group(function () {
            Route::get('/scores', [NTUserController::class, 'getScores']);
            Route::get('/integration-receipts', [NTIntegrationController::class, 'getReceipts']);
            Route::get('/integration-receipts/{receiptId}', [NTIntegrationController::class, 'getReceipt']);
        });

    Route::prefix('receipts')->middleware('auth:sanctum')
        ->group(function () {
            Route::post('/upload-photo', [ReceiptController::class, 'uploadPhoto']);
            Route::post('/submit-qr', [ReceiptController::class, 'submitQr']);
            Route::post('/submit-manual', [ReceiptController::class, 'submitManual']);
            Route::get('/', [ReceiptController::class, 'index']);
            Route::get('/{id}', [ReceiptController::class, 'show']);
        });
});

Route::post('/max-bot/webhook', MaxBotWebhookController::class)
    ->middleware(MaxBotApiSecretValidation::class)
    ->name('max-bot.webhook');
