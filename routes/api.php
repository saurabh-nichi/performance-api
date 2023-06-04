<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LogsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('user')->group(function () {
    Route::post('/', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('reset-password', [AuthController::class, 'generateResetPasswordToken']);
});
Route::middleware(['auth:api'])->group(function () {
    Route::prefix('user')->group(function () {
        Route::post('verify-email/send', [AuthController::class, 'sendVerificationEmail']);
        Route::post('verify-email/confirm', [AuthController::class, 'verifyEmail']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
Route::post('read-request-logs', [LogsController::class, 'readRequestLogs'])->name('requestLog');
