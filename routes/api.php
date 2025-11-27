<?php

use App\Http\Controllers\AgentProfileController;
use App\Http\Controllers\SupportRequestsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashOperationController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\CurrencyRatesController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BeneficiaryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    // Google Auth
    Route::get('google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
    
    // Registration & Login
    Route::post('register/user', [AuthController::class, 'registerUser']);
    Route::post('register/agent', [AuthController::class, 'registerAgent']);
    Route::post('login', [AuthController::class, 'login']);
});

// Currency routes (public)
Route::prefix('currency')->group(function () {
    Route::get('list', [CurrencyRatesController::class, 'getCurrencies']);
    Route::post('validate', [CurrencyRatesController::class, 'validateCurrency']);
});

// Broadcasting auth
Route::post('broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware(['jwt']);

// Protected routes (JWT required)
Route::middleware(['jwt'])->group(function () {
    
    // Auth management
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::patch('{notificationId}/read', [NotificationController::class, 'markAsRead']);
    });

    // Services & Reviews
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{id}', [ServiceController::class, 'show']);
    Route::apiResource('reviews', ReviewController::class)->except(['create', 'edit']);

    // Agents
    Route::prefix('agents')->group(function () {
        Route::get('/', [AgentProfileController::class, 'listAgents']);
        Route::get('{agentId}', [AgentProfileController::class, 'getAgentProfile']);
    });

    // User-specific routes
    Route::prefix('user')->middleware(['role:user'])->group(function () {
        Route::post('cash-operations/{id}/approve', [CashOperationController::class, 'approve']);
        Route::post('cash-operations/{id}/reject', [CashOperationController::class, 'reject']);
    });

    // Agent-specific routes
    Route::prefix('agent')->middleware(['role:agent'])->group(function () {
        Route::get('profile', [AgentProfileController::class, 'getPersonalProfile']);
        Route::put('profile', [AgentProfileController::class, 'updateProfile']);
        Route::post('cash-operations', [CashOperationController::class, 'create']);
        Route::post('cash-operations/{id}/cancel', [CashOperationController::class, 'cancel']);
    });

    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::post('wallet-to-wallet', [TransactionController::class, 'walletToWalletTransfer']);
        Route::put('approve/{id}', [TransactionController::class, 'approveWalletToWalletTransfer']);
        Route::put('reject/{id}', [TransactionController::class, 'rejectWalletToWalletTransfer']);
        Route::get('wallet-to-wallet-history', [TransactionController::class, 'getWalletToWalletTransactions']);
    });

    // Wallets
    Route::prefix('wallets')->group(function () {
        Route::get('/', [WalletController::class, 'getAllWallets']);
        Route::post('/', [WalletController::class, 'store']);
        Route::get('{id}', [WalletController::class, 'show']);
        Route::patch('{id}', [WalletController::class, 'destroy']);
        
        // Admin wallet routes
        Route::middleware(['role:admin'])->group(function () {
            Route::get('admin', [WalletController::class, 'adminGetAllWallets']);
            Route::get('admin/{user_id}', [WalletController::class, 'adminUserWallets']);
        });
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::post('recharge-wallet', [PaymentController::class, 'rechargeWallet']);
        Route::get('wallet-balance', [PaymentController::class, 'getWalletBalance']);
        Route::get('payment-methods', [PaymentController::class, 'listPaymentMethods']);
    });

    // Support requests
    Route::prefix('support')->group(function () {
        Route::post('request', [SupportRequestsController::class, 'store']);
        Route::get('request/{id}', [SupportRequestsController::class, 'showSingleRequest']);
        Route::get('request', [SupportRequestsController::class, 'filterSupportRequests']);
        
        // Admin support routes
        Route::middleware(['role:admin'])->group(function () {
            Route::get('request-admin/{id}', [SupportRequestsController::class, 'showSingleRequestAdmin']);
            Route::put('request/{id}', [SupportRequestsController::class, 'update']);
        });
    });

    // Beneficiaries
    Route::prefix('beneficiaries')->group(function () {
        Route::get('/', [BeneficiaryController::class, 'index']);
        Route::post('create', [BeneficiaryController::class, 'create']);
        Route::get('view/{id}', [BeneficiaryController::class, 'show']);
        Route::put('update/{id}', [BeneficiaryController::class, 'update']);
        Route::delete('destroy/{id}', [BeneficiaryController::class, 'destroy']);
    });

    // Admin-only routes
    Route::middleware(['role:admin'])->group(function () {
        // Services management
        Route::post('services', [ServiceController::class, 'store']);
        Route::put('services/{id}', [ServiceController::class, 'update']);
        Route::delete('services/{id}', [ServiceController::class, 'destroy']);

        // Agent management
        Route::prefix('admin')->group(function () {
            Route::patch('agents/{agentId}/status', [AgentProfileController::class, 'updateStatus']);
            Route::patch('agents/commission/update-all', [AgentProfileController::class, 'updateAllCommissions']);
        });
    });
});

Route::middleware(['jwt'])->prefix('/refund')->group(function () {
    Route::post('/request-create',[\App\Http\Controllers\RefundRequestsController::class,'create']);
    Route::get('/request-view/{id}',[\App\Http\Controllers\RefundRequestsController::class,'viewSingleRefundRequest']);
    Route::put('/request-cancel/{id}',[\App\Http\Controllers\RefundRequestsController::class,'cancelRefund']);

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/requests-view-all',[\App\Http\Controllers\RefundRequestsController::class,'viewAllRefundRequests']);
        Route::put('/request-complete/{id}',[\App\Http\Controllers\RefundRequestsController::class,'completeRefund']);
        Route::put('/request-reject/{id}',[\App\Http\Controllers\RefundRequestsController::class,'rejectRefund']);
    });

});
