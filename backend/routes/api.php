<?php

use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BusinessHoursController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SlaPolicyController;
use App\Http\Controllers\Api\TicketActivityController;
use App\Http\Controllers\Api\TicketAssignmentController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketReplyController;
use App\Http\Controllers\Api\TicketStatusController;
use Illuminate\Support\Facades\Route;

Route::get('ping', fn () => ['message' => 'Support Desk API is running.']);

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::patch('me/availability', [AvailabilityController::class, 'update']);

    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::patch('categories/{category}', [CategoryController::class, 'update']);

    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::patch('users/{user}', [UserController::class, 'update']);

    Route::get('settings/sla-policies', [SlaPolicyController::class, 'index']);
    Route::patch('settings/sla-policies/{sla_policy}', [SlaPolicyController::class, 'update']);
    Route::get('settings/business-hours', [BusinessHoursController::class, 'show']);
    Route::get('agents', [AgentController::class, 'index']);

    Route::get('imports/template', [ImportController::class, 'template']);
    Route::get('imports', [ImportController::class, 'index']);
    Route::post('imports', [ImportController::class, 'store']);
    Route::get('imports/{import}', [ImportController::class, 'show']);
    Route::get('imports/{import}/errors', [ImportController::class, 'errors']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);

    Route::apiResource('tickets', TicketController::class)->only(['index', 'store', 'show', 'update']);
    Route::post('tickets/{ticket}/status', TicketStatusController::class);
    Route::post('tickets/{ticket}/assign', [TicketAssignmentController::class, 'assign']);
    Route::post('tickets/{ticket}/claim', [TicketAssignmentController::class, 'claim']);
    Route::delete('tickets/{ticket}/assignment', [TicketAssignmentController::class, 'unassign']);
    Route::get('tickets/{ticket}/replies', [TicketReplyController::class, 'index']);
    Route::post('tickets/{ticket}/replies', [TicketReplyController::class, 'store']);
    Route::post('tickets/{ticket}/attachments', [AttachmentController::class, 'store']);
    Route::get('tickets/{ticket}/activities', [TicketActivityController::class, 'index']);
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download']);
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);
});
