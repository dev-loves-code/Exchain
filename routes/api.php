<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\CurrencyRatesController;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\AgentProfileController;

// Google Auth routes
Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register/user', [AuthController::class, 'registerUser']);
    Route::post('/register/agent', [AuthController::class, 'registerAgent']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware(['jwt']);

// Routes requiring JWT auth
Route::middleware(['jwt'])->group(function () {

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::patch('/{notificationId}/read', [NotificationController::class, 'markAsRead']);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // services
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{id}', [ServiceController::class, 'show']);

    // reviews
    Route::apiResource('reviews', ReviewController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy']);

    // Agents routes
    Route::prefix('agents')->group(function () {
        Route::get('/', [AgentProfileController::class, 'listAgents']);
        Route::get('/{agentId}', [AgentProfileController::class, 'getAgentProfile']);
    });

    // user personal routes
    Route::prefix('user')->middleware(['role:user'])->group(function () {
        Route::post('/cash-operations/{id}/approve', [CashOperationController::class, 'approve']);
        Route::post('/cash-operations/{id}/reject', [CashOperationController::class, 'reject']);
    });

    // agent personal routes
    Route::prefix('agent')->middleware(['role:agent'])->group(function () {
        Route::get('/profile', [AgentProfileController::class, 'getPersonalProfile']);
        Route::put('/profile', [AgentProfileController::class, 'updateProfile']);

        Route::post('/cash-operations', [CashOperationController::class, 'create']);
        Route::post('/cash-operations/{id}/cancel', [CashOperationController::class, 'cancel']);
    });

    // Transactions (exchange-rate feature)
    Route::prefix('Transactions')->group(function () {
        Route::post('/WalletToWallet', [TransactionController::class, 'walletToWalletTransfer'])->name('walletToWalletTransfer');
        Route::put('/ApproveTransaction/{id}', [TransactionController::class, 'approveWalletToWalletTransfer'])->name('approveTransfer');
        Route::put('/RejectTransaction/{id}', [TransactionController::class, 'rejectWalletToWalletTransfer'])->name('rejectTransfer');
        Route::get('/WalletToWalletHistory', [TransactionController::class, 'getWalletToWalletTransactions']);

        // Wallet To person:
        // User
        Route::post('/initiate-wallet-to-person', [TransactionController::class, 'initiateWalletToPersonTransfer'])->name('walletToPerson');
        Route::get('receipt/{id}', [TransactionController::class, 'getReceipt']);
        Route::get('transactions-history-w2p', [TransactionController::class, 'getTransactions']);

        Route::prefix('agent/wallet-to-person')
            ->middleware(['role:agent'])
            ->group(function () {
                Route::post('verify', [TransactionController::class, 'verifyTransactionAgent']);
                Route::post('complete', [TransactionController::class, 'completeTransactionAgent']);
            });
    });

    // Wallets (exchange-rate feature)
    Route::prefix('wallets')->group(function () {

        /****ADMIN SIDE****/
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/admin', [WalletController::class, 'adminGetAllWallets']);
            Route::get('/admin/{user_id}', [WalletController::class, 'adminUserWallets']);
        });

        /****USER SIDE****/
        Route::post('/', [WalletController::class, 'store']);
        Route::get('/', [WalletController::class, 'getAllWallets']);
        Route::patch('/{id}', [WalletController::class, 'destroy']);
        Route::get('/{id}', [WalletController::class, 'show']);
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
            Route::patch('/agents/commission/update-all', [AgentProfileController::class, 'updateAllCommissions']);
        });
    });

// Currency routes (exchange-rate feature)
    Route::prefix('currency')->group(function () {
        Route::get('/list', [CurrencyRatesController::class, 'getCurrencies']);
        Route::post('/validate', [CurrencyRatesController::class, 'validateCurrency']);
    });
});
