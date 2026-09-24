<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\DashboardController;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\TicketMessageController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\TicketAnalyticsController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Endpoint Publik
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/loginSso', [AuthController::class, 'loginSso']);

// Endpoint Chatbot Landing Page
Route::post('/chatbot/ask', [KnowledgeBaseController::class, 'searchAnswer']);

// Protected Routes (Sanctum Authenticated)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth & Profile Routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateID']);
    Route::get('/me', [AuthController::class, 'me']);    
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/tickets/stats', [TicketController::class, 'stats']);
  

    // Dashboard untuk admin utama dan admin departemen.
    Route::middleware('role:1,2,3')->get('/dashboard', [DashboardController::class, 'index']);

    // Role 1 & 2 (Super Admin / Admin Departemen Utama)
    Route::middleware('role:1,2')->group(function () {
        Route::apiResource('/users', UserController::class);
        Route::apiResource('/departments', DepartmentController::class);
        Route::apiResource('knowledge-base', KnowledgeBaseController::class);
    });

    // Role 1, 2, 3, 4 (Akses Tiket, Kategori, & Chat)
    Route::middleware('role:1,2,3,4')->group(function () {
        // Tickets & Bulk Status
        Route::post('/tickets/bulk-status', [TicketController::class, 'bulkUpdateStatus']);
        Route::get('/tickets/export/pdf', [TicketController::class, 'exportPdf']);
        Route::get('/tickets/export/excel', [TicketController::class, 'exportExcel']);
        Route::apiResource('tickets', TicketController::class);

              // Ticket Messages / Chat
        Route::get('/tickets/{ticket}/messages', [TicketMessageController::class, 'index']);
        Route::post('/tickets/{ticket}/messages', [TicketMessageController::class, 'store']);
       // 1. Route Hapus SELURUH Pesan pada Tiket
        Route::delete('/tickets/{ticket}/messages', [TicketMessageController::class, 'destroyAll']);
        // 2. Route Hapus SATU Pesan Spesifik
        Route::delete('/tickets/{ticket}/messages/{message}', [TicketMessageController::class, 'destroy']);
    });

    Route::middleware('role:1,2,3')->prefix('analytics')->group(function () {
    Route::get('/departments', [TicketAnalyticsController::class, 'getDepartments']);
    Route::get('/ticket-status', [TicketAnalyticsController::class, 'getStatusStats']);
    Route::get('/department-tickets', [TicketAnalyticsController::class, 'getDepartmentTickets']);
    Route::get('/resolution-time', [TicketAnalyticsController::class, 'getResolutionTime']);
    Route::get('/summary', [TicketAnalyticsController::class, 'getSummary']);
    Route::get('/priority_recent', [TicketAnalyticsController::class, 'getPriorityAndRecent']);
    });

    Route::middleware('role:1,2,3')->prefix('reports')->group(function () {
        Route::get('/departments', [ReportController::class, 'departments']);
        Route::get('/tickets', [ReportController::class, 'tickets']);
        Route::get('/export/{type}', [ReportController::class, 'export']);
    });

    Route::post('/tickets/bulk-action', [TicketController::class, 'bulkAction']);

   
});