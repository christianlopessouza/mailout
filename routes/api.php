<?php

use App\Http\Master\Controllers\ListEmailsByAccountController;
use App\Http\Master\Controllers\ListEmailsByClientController;
use App\Http\Master\Controllers\RegisterAccountController;
use App\Http\Master\Controllers\SendEmailController;
use Illuminate\Support\Facades\Route;

// Consultas exteriores
Route::middleware(['auth.public.client'])->prefix('public/client')->group(function () {
    Route::post('/listEmails', ListEmailsByClientController::class);
    Route::post('/registerAccount', RegisterAccountController::class);
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
