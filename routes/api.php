<?php

use App\Http\Master\Controllers\ListEmailsByAccountController;
use App\Http\Master\Controllers\ListEmailsByClientController;
use App\Http\Master\Controllers\SendEmailByClientController;
use App\Http\Master\Controllers\SendEmailController;
use Illuminate\Support\Facades\Route;

// Consultas exteriores
Route::middleware(['auth.public.client'])->prefix('public/client')->group(function () {
    Route::post('/list-emails', ListEmailsByClientController::class);
    Route::post('/send-email', SendEmailByClientController::class);

    // Route::post('/emails/batch', [StoreBatchController::class, 'storeBatch']);
    // Route::get('/emails/batch/send/{amount}', [SendBatchController::class, 'sendBatch']);
});

Route::middleware(['auth.public.account'])->prefix('public/account')->group(function () {
    Route::post('/send-email', SendEmailController::class);

    Route::post('/list-emails', ListEmailsByAccountController::class);
});

Route::get('/ping', function () {
    return response()->json(['ok' => true]);
});

Route::post('/email-complement/save', \App\Http\Master\Controllers\SaveEmailComplementController::class);
    
