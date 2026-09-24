<?php

use App\Http\Controllers\B2bClientController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/sso', [B2bClientController::class, 'getData']);
Route::get('/mhs', [B2bClientController::class, 'getMhs']);