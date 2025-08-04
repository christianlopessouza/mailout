<?php

use App\Http\Master\Controllers\FilterEmailsByClientController;
use App\Http\Master\Controllers\FilterEmailsByAccountController;
use App\Http\Master\Controllers\SendEmailByClientController;
use App\Http\Master\Controllers\SendEmailController;
use Illuminate\Support\Facades\Route;

// Consultas exteriores
Route::middleware(['auth.public.client'])->prefix('public/client')->group(function () {
    Route::get('/list-emails', FilterEmailsByClientController::class);
    Route::post('/send-email', SendEmailByClientController::class);

    // Route::post('/emails/batch', [StoreBatchController::class, 'storeBatch']);
    // Route::get('/emails/batch/send/{amount}', [SendBatchController::class, 'sendBatch']);
});

Route::middleware(['auth.public.account'])->prefix('public/account')->group(function () {
    Route::post('/send-email', SendEmailController::class);

    Route::get('/list-emails', FilterEmailsByAccountController::class);
});

Route::get('/ping', function () {
    return response()->json(['ok' => true]);
});
