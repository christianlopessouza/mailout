<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\SwitchAccountController;
use App\Http\Controllers\ListEmailsController;
use App\Http\Controllers\SendEmailController;

use App\Http\Controllers\GetEmailHistoryController;
use App\Http\Controllers\StoreBatchController;
use App\Http\Controllers\SendBatchController;

// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

// Consultas realizadas pelo Smail
// Route::middleware(['auth.jwt'])->group(function () {
//     Route::post('/login', [LoginController::class, 'login']);
//     Route::get('/account/switch', [SwitchAccountController::class, 'switchAccount']);
//     Route::get('/emails', [ListEmailsController::class, 'listEmails']);
//     Route::post('/emails/send', [SendEmailController::class, 'send']);
// });

// // Consultas exteriores
// Route::prefix('public')->group(function () {
//     Route::get('/emails', [GetEmailHistoryController::class, 'getEmailHistory']);
//     Route::post('/emails/batch', [StoreBatchController::class, 'storeBatch']);
//     Route::get('/emails/batch/send/{amount}', [SendBatchController::class, 'sendBatch']);
// });
