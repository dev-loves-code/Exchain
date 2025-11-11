<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/register/user', [AuthController::class, 'registerUser']);
    Route::post('/register/agent', [AuthController::class, 'registerAgent']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['jwt'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware(['jwt'])->prefix('/refund')->group(function () {
    Route::post('/request-create',[\App\Http\Controllers\RefundRequestsController::class,'create']);
    Route::get('/request-view/{id}',[\App\Http\Controllers\RefundRequestsController::class,'viewSingleRefundRequest']);

});
