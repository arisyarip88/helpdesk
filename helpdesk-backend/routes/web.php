<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Callback SSO fallback jika redirect tanpa prefix /api
Route::get('/loginsso', [AuthController::class, 'handleSsoCallback']);
