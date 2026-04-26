<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\FinanceController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'changePassword']);

    // Admin User CRUD
    Route::apiResource('users', UserController::class);

    // Reports
    Route::apiResource('reports', ReportController::class);

    // Tasks
    Route::apiResource('tasks', TaskController::class);

    // Finances
    Route::get('finances/summary', [FinanceController::class, 'summary']);
    Route::apiResource('finances', FinanceController::class);
});
