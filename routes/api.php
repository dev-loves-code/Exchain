<?php

use App\Http\Controllers\BeneficiaryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\AgentProfileController;
use App\Http\Controllers\PaymentController;


// Google Auth routes
Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// Auth routes use App\Http\Controllers\PaymentController;

Route::prefix('auth')->group(function () {
    Route::post('/register/user', [AuthController::class, 'registerUser']);
    Route::post('/register/agent', [AuthController::class, 'registerAgent']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Agents routes
Route::prefix('agents')->group(function () {
    Route::get('/', [AgentProfileController::class, 'listAgents']);
    Route::get('/{agentId}', [AgentProfileController::class, 'getAgentProfile']);
});

// Routes requiring JWT auth
Route::middleware(['jwt'])->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // services
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{id}', [ServiceController::class, 'show']);

    // reviews
    Route::apiResource('reviews', ReviewController::class)
        ->only(['index', 'show','store','update','destroy']);

    // agent personal routes
    Route::prefix('agent')->middleware(['role:agent'])->group(function () {
        Route::get('/profile', [AgentProfileController::class, 'getPersonalProfile']);
        Route::put('/profile', [AgentProfileController::class, 'updateProfile']);
    });

});

// Admin-only routes
Route::middleware(['jwt', 'role:admin'])->group(function () {
    // services
    Route::post('services', [ServiceController::class, 'store']);
    Route::put('services/{id}', [ServiceController::class, 'update']);
    Route::delete('services/{id}', [ServiceController::class, 'destroy']);

    // admin agent status
    Route::prefix('admin')->group(function () {
        Route::patch('/agents/{agentId}/status', [AgentProfileController::class, 'updateStatus']);
    });
});

Route::middleware(['jwt'])->prefix('payments')->group(function () {

    Route::post('/recharge-wallet', [PaymentController::class, 'rechargeWallet']);

    Route::get('/wallet-balance', [PaymentController::class, 'getWalletBalance']);
    Route::get('/payment-methods', [PaymentController::class, 'listPaymentMethods']);

});

// Beneficiaries Routes

Route::middleware(['jwt'])->prefix('beneficiaries') -> group(function () {
    Route::get('/', [BeneficiaryController::class, 'index']);
    Route::post('/create', [BeneficiaryController::class, 'create']);
    Route::get('view/{id}', [BeneficiaryController::class, 'show']);
    Route::put('update/{id}', [BeneficiaryController::class, 'update']);
    Route::delete('destroy/{id}', [BeneficiaryController::class, 'destroy']);
});
