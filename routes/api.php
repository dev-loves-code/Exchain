<?php

use App\Http\Controllers\SupportRequestsController;
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

Route::middleware(['jwt'])->group(function () {
    Route::prefix('support')->group(function () {
    Route::post('/request', [SupportRequestsController::class,'store']);
    Route::get('/request', [SupportRequestsController::class,'viewAllRequests']);
    Route::get('/request/{id}', [SupportRequestsController::class,'showSingleRequest']);
    Route::get('/requestFilter', [SupportRequestsController::class,'filterSupportRequests']);
});
});
