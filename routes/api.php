<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;

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
    Route::prefix('Transactions')->group(function () {
        Route::post('/WalletToWallet', [TransactionController::class, 'walletToWalletTransfer'])->name('walletToWalletTransfer');
        Route::put('/ApproveTransaction/{id}', [TransactionController::class, 'approveWalletToWalletTransfer'])->name('approveTransfer');
        Route::put('/RejectTransaction/{id}', [TransactionController::class, 'rejectWalletToWalletTransfer'])->name('rejectTransfer');
        Route::get('/WalletToWalletHistory', [TransactionController::class, 'getWalletToWalletTransactions']);
    });
});