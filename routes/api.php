<?php

use App\Http\Master\Controllers\ListEmailsByAccountController;
use App\Http\Master\Controllers\ListEmailsByClientController;
use App\Http\Master\Controllers\RegisterAccountController;
use App\Http\Master\Controllers\FilterEmailsByClientController;
use App\Http\Master\Controllers\FilterEmailsByAccountController;
use App\Http\Master\Controllers\SendEmailByClientController;
use App\Http\Master\Controllers\SendEmailController;
use App\Http\Master\Controllers\ListEmailByIdController;
// use App\Http\Master\Controllers\ListEmailsByThreadIdController;
use App\Http\Master\Controllers\SaveEmailController;
use Illuminate\Support\Facades\Route;
use App\Http\Master\Controllers\UpdateEmailComplementController;

Route::options('{any}', function () {
    return response()->noContent(204)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With, Accept')
        ->header('Access-Control-Max-Age', '86400');
})->where('any', '.*');

// Consultas exteriores
Route::middleware(['auth.public.client'])->prefix('public/client')->group(function () {
    Route::post('/registerAccount', RegisterAccountController::class);
    Route::get('/list-emails', FilterEmailsByClientController::class);
    Route::get('/list-email/{id}', ListEmailByIdController::class);
    Route::post('/send-email', SendEmailByClientController::class);
    Route::post('/save-email', SaveEmailController::class);
    // Route::post('/emails/batch', [StoreBatchController::class, 'storeBatch']);
    // Route::get('/emails/batch/send/{amount}', [SendBatchController::class, 'sendBatch']);
    // Route::get('/list-emails-thread/{thread_id}', ListEmailsByThreadIdController::class);
    Route::post('/update-email-complement/{id}', UpdateEmailComplementController::class);
});

Route::middleware(['auth.public.account'])->prefix('public/account')->group(function () {
    Route::post('/send-email', SendEmailController::class);

    Route::get('/list-emails', FilterEmailsByAccountController::class);
});

Route::get('/ping', function () {
    return response()->json(['ok' => true]);
});

// Rota temporária para teste (sem middleware)
Route::post('/test-update-email-complement/{id}', UpdateEmailComplementController::class);

Route::post('/email-complement/save', \App\Http\Master\Controllers\SaveEmailComplementController::class);
