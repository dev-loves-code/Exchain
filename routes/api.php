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

/**
Routes for Support Request
 **/


Route::middleware(['jwt'])->prefix('support')->group(function () {
    /**Normal User Routes**/
    Route::post('/request', [SupportRequestsController::class,'store']);

    /**Common Routes**/
    Route::get('/request/{id}', [SupportRequestsController::class,'showSingleRequest']);
    Route::get('/request', [SupportRequestsController::class,'filterSupportRequests']);

    /**Admin Route**/
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/requestAdmin/{id}', [SupportRequestsController::class,'showSingleRequestAdmin']);
        Route::put('/request/{id}', [SupportRequestsController::class, 'update']);
    });

});
