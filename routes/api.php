<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\AgentProfileController;


Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);


Route::prefix('auth')->group(function () {
    Route::post('/register/user', [AuthController::class, 'registerUser']);
    Route::post('/register/agent', [AuthController::class, 'registerAgent']);
    Route::post('/login', [AuthController::class, 'login']);
});


Route::prefix('agents')->group(function () {
    
    Route::get('/', [AgentProfileController::class, 'listAgents']);
    
    
    Route::get('/{agentId}', [AgentProfileController::class, 'getAgentProfile']);
});


Route::middleware(['jwt'])->group(function () {
    
    
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

     Route::get('/agents', [AgentProfileController::class, 'listAgents']);
    
    
     Route::prefix('agent')->middleware(['role:agent'])->group(function () {
        
        Route::get('/profile', [AgentProfileController::class, 'getPersonalProfile']);
        
        
        Route::put('/profile', [AgentProfileController::class, 'updateProfile']);
    });
    
    
    Route::prefix('admin')->middleware(['role:admin'])->group(function () {
        
        Route::patch('/agents/{agentId}/status', [AgentProfileController::class, 'updateStatus']);
    });
});