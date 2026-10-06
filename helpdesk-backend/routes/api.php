<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\PublicComplaintController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TicketAlertController;
use App\Http\Controllers\Api\TicketAnalyticsController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketMessageController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Endpoint Publik
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// SSO UNPAM (sesuai panduan integrasi sso.pdf)
Route::get('/sso/url', [AuthController::class, 'ssoUrl']);
Route::get('/loginsso', [AuthController::class, 'handleSsoCallback']);
Route::post('/loginsso', [AuthController::class, 'loginSso']);
Route::post('/loginSso', [AuthController::class, 'loginSso']);

// Endpoint Chatbot Landing Page
Route::post('/chatbot/ask', [KnowledgeBaseController::class, 'searchAnswer'])->middleware('throttle:60,1');
Route::get('/public/categories', [PublicComplaintController::class, 'categories']);
Route::get('/public/complaints/captcha', [PublicComplaintController::class, 'captcha'])->middleware('throttle:30,1');
Route::post('/public/complaints', [PublicComplaintController::class, 'store'])->middleware('throttle:5,1');

// Protected Routes (Sanctum Authenticated)
Route::middleware('auth:sanctum')->group(function () {

    Route::middleware('role:1,2')->prefix('admin/public-complaints')->group(function () {
        Route::get('/', [PublicComplaintController::class, 'index']);
        Route::post('/', [PublicComplaintController::class, 'adminStore']);
        Route::get('/{complaint}/attachment', [PublicComplaintController::class, 'downloadAttachment']);
        Route::get('/{complaint}', [PublicComplaintController::class, 'show']);
        Route::put('/{complaint}', [PublicComplaintController::class, 'update']);
        Route::delete('/{complaint}', [PublicComplaintController::class, 'destroy']);
    });

    // Auth & Profile Routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateID']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/tickets/stats', [TicketController::class, 'stats']);
    Route::get('/ticket-status-notifications', [TicketController::class, 'statusNotifications'])
        ->middleware('role:1,2,3,4');
    Route::post('/ticket-status-notifications/{notification}/read', [TicketController::class, 'markStatusNotificationRead'])
        ->middleware('role:1,2,3,4');

    Route::middleware('role:1,2')->group(function () {
        Route::get('/ticket-handling-settings', [TicketAlertController::class, 'settings']);
        Route::put('/ticket-handling-settings', [TicketAlertController::class, 'updateSettings']);
        Route::post('/tickets/{ticket}/warnings', [TicketAlertController::class, 'sendWarning']);

    });

    Route::middleware('role:1,2,3')->group(function () {
        Route::apiResource('/categories', CategoryController::class);
    });

    Route::middleware('role:3')->group(function () {
        Route::get('/ticket-warnings', [TicketAlertController::class, 'notifications']);
        Route::post('/ticket-warnings/{warning}/read', [TicketAlertController::class, 'markRead']);
    });

    // Dashboard untuk admin utama dan admin departemen.
    Route::middleware('role:1,2,3')->get('/dashboard', [DashboardController::class, 'index']);

    // Role 1 & 2 (Super Admin / Admin Departemen Utama)
    Route::middleware('role:1,2')->group(function () {
        Route::apiResource('/users', UserController::class);
        Route::apiResource('/departments', DepartmentController::class);
        Route::get('/knowledge-base/unanswered', [KnowledgeBaseController::class, 'unanswered']);
        Route::post('/knowledge-base/unanswered/{question}/knowledge', [KnowledgeBaseController::class, 'convertUnanswered']);
        Route::delete('/knowledge-base/unanswered/{question}', [KnowledgeBaseController::class, 'destroyUnanswered']);

    });

    // Role 1, 2, 3, 4 (Akses Tiket, Kategori, & Chat)
    Route::middleware('role:1,2,3')->apiResource('knowledge-base', KnowledgeBaseController::class);
    Route::middleware('role:1,2,3,4')->group(function () {
        // Tickets & Bulk Status
        Route::post('/tickets/bulk-status', [TicketController::class, 'bulkUpdateStatus']);
        Route::get('/tickets/export/pdf', [TicketController::class, 'exportPdf']);
        Route::get('/tickets/export/excel', [TicketController::class, 'exportExcel']);
        Route::get('/tickets/changes', [TicketController::class, 'changes'])
            ->middleware('role:1,2,3,4');
        Route::get('/tickets/chat-notifications', [TicketController::class, 'chatNotifications'])
            ->middleware('role:1,2,3,4');
        Route::apiResource('tickets', TicketController::class);

        // Ticket Messages / Chat
        Route::get('/tickets/{ticket}/messages', [TicketMessageController::class, 'index']);
        Route::post('/tickets/{ticket}/messages', [TicketMessageController::class, 'store']);
        // 1. Route Hapus SELURUH Pesan pada Tiket
        Route::delete('/tickets/{ticket}/messages', [TicketMessageController::class, 'destroyAll']);
        // 2. Route Hapus SATU Pesan Spesifik
        Route::delete('/tickets/{ticket}/messages/{message}', [TicketMessageController::class, 'destroy']);
    });

    Route::middleware('role:4')->post('/tickets/{id}/rating', [TicketController::class, 'rate']);

    Route::middleware('role:1,2,3')->prefix('analytics')->group(function () {
        Route::get('/departments', [TicketAnalyticsController::class, 'getDepartments']);
        Route::get('/ticket-status', [TicketAnalyticsController::class, 'getStatusStats']);
        Route::get('/department-tickets', [TicketAnalyticsController::class, 'getDepartmentTickets']);
        Route::get('/resolution-time', [TicketAnalyticsController::class, 'getResolutionTime']);
        Route::get('/summary', [TicketAnalyticsController::class, 'getSummary']);
        Route::get('/priority_recent', [TicketAnalyticsController::class, 'getPriorityAndRecent']);
        Route::get('/rating-ranking', [TicketAnalyticsController::class, 'getRatingRanking']);
        Route::get('/category-frequency', [TicketAnalyticsController::class, 'getCategoryFrequency']);
    });

    Route::middleware('role:1,2,3')->prefix('reports')->group(function () {
        Route::get('/departments', [ReportController::class, 'departments']);
        Route::get('/tickets', [ReportController::class, 'tickets']);
        Route::get('/export/{type}', [ReportController::class, 'export']);
    });

    Route::middleware('role:1,2')->post('/tickets/bulk-action', [TicketController::class, 'bulkAction']);

});
