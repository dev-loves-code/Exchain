<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;

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


Route::middleware(['jwt'])->group(function(){
    Route::prefix('wallets')->group(function(){
       
        /****ADMIN SIDE****/
       Route::middleware(['role:admin'])->group(function(){
            Route::get('/admin', [WalletController::class, 'adminGetAllWallets']);
            Route::get('/admin/{user_id}', [WalletController::class, 'adminUserWallets']);
       }); 

        /****USER SIDE****/
        Route::post('/', [WalletController::class, 'store']);
        Route::get('/', [WalletController::class, 'getAllWallets']);
        Route::delete('/{id}', [WalletController::class, 'destroy']);
        Route::get('/{id}', [WalletController::class, 'show']);
    });
});