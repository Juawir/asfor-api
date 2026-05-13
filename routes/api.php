<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\NotificationController;

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
    Route::patch('reports/{report}/approve', [ReportController::class, 'approve']);
    Route::patch('reports/{report}/reject', [ReportController::class, 'reject']);

    // Tasks
    Route::apiResource('tasks', TaskController::class);

    // Finances
    Route::get('finances/summary', [FinanceController::class, 'summary']);
    Route::apiResource('finances', FinanceController::class);

    // Labs & Inventory
    Route::get('inventory-users', [\App\Http\Controllers\Api\LabController::class, 'getInventoryUsers']);
    Route::post('labs/{lab}/assign-pics', [\App\Http\Controllers\Api\LabController::class, 'assignPics']);
    Route::apiResource('labs', \App\Http\Controllers\Api\LabController::class)->only(['index', 'show', 'store', 'destroy']);
    Route::apiResource('labs.items', \App\Http\Controllers\Api\InventoryItemController::class)->only(['store', 'update', 'destroy']);

    // Events
    Route::apiResource('events', EventController::class)->only(['index', 'store', 'update', 'destroy']);

    // Elections
    Route::get('elections/current', [\App\Http\Controllers\Api\ElectionController::class, 'current']);
    Route::post('elections', [\App\Http\Controllers\Api\ElectionController::class, 'store']);
    Route::post('elections/{id}/vote', [\App\Http\Controllers\Api\ElectionController::class, 'vote']);
    Route::patch('elections/{id}/end', [\App\Http\Controllers\Api\ElectionController::class, 'end']);
    Route::delete('elections/{id}', [\App\Http\Controllers\Api\ElectionController::class, 'destroy']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
});
