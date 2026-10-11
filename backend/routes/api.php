<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TicketAssignmentController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketStatusController;
use Illuminate\Support\Facades\Route;

Route::get('ping', fn () => ['message' => 'Support Desk API is running.']);

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('agents', [AgentController::class, 'index']);

    Route::apiResource('tickets', TicketController::class)->only(['index', 'store', 'show', 'update']);
    Route::post('tickets/{ticket}/status', TicketStatusController::class);
    Route::post('tickets/{ticket}/assign', [TicketAssignmentController::class, 'assign']);
    Route::post('tickets/{ticket}/claim', [TicketAssignmentController::class, 'claim']);
    Route::delete('tickets/{ticket}/assignment', [TicketAssignmentController::class, 'unassign']);
});
