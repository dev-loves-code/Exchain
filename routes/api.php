<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\AgentProfileController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AgentDashboardController;
use App\Http\Controllers\TransactionTrackingController;
use App\Http\Controllers\SMSController;
use App\Services\SMSNotificationService;
use App\Http\Controllers\GitHubAuthController;
use App\Http\Controllers\ChatController;
use App\Models\Transaction;
use App\Events\TransactionStatusUpdated;
    Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

    Route::get('auth/github', [GitHubAuthController::class, 'redirectToGitHub']);
    Route::get('auth/github/callback', [GitHubAuthController::class, 'handleGitHubCallback']);

    Route::get('/transactions/{id}/tracking', [TransactionTrackingController::class, 'show']);


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

        Route::get('/transactions', [TransactionTrackingController::class, 'index']);
        Route::get('/transactions/{id}', [TransactionTrackingController::class, 'show']);

        Route::get('services', [ServiceController::class, 'index']);
        Route::get('services/{id}', [ServiceController::class, 'show']);

        Route::apiResource('reviews', ReviewController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy']);

        Route::get('/user/dashboard', [UserDashboardController::class, 'dashboard']);

        Route::prefix('agent')->middleware(['role:agent'])->group(function () {
        Route::get('/dashboard', [AgentDashboardController::class, 'dashboard']);
        Route::get('/profile', [AgentProfileController::class, 'getPersonalProfile']);
        Route::put('/profile', [AgentProfileController::class, 'updateProfile']);
    });


    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'dashboard']);


        Route::post('services', [ServiceController::class, 'store']);
        Route::put('services/{id}', [ServiceController::class, 'update']);
        Route::delete('services/{id}', [ServiceController::class, 'destroy']);

        Route::patch('/admin/agents/{agentId}/status', [AgentProfileController::class, 'updateStatus']);
        Route::get('/admin/agents', [AgentProfileController::class, 'listAgents']);

    });
});


    Route::prefix('agents')->group(function () {
        Route::get('/', [AgentProfileController::class, 'listAgents']);
        Route::get('/{agentId}', [AgentProfileController::class, 'getAgentProfile']);
});
Route::post('/chat/ask', [ChatController::class, 'ask']);


Route::get('/test-wa/{id}', function($id) {
    $transaction = Transaction::findOrFail($id);
    event(new TransactionStatusUpdated($transaction));
    return response()->json([
        'status' => 'event fired',
        'transaction_id' => $transaction->id
    ]);
});