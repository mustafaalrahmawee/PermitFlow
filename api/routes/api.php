<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
 * API routes (Sanctum API bearer-token auth, §5.5). Clients send a personal
 * access token as `Authorization: Bearer <token>`; there is no stateful SPA
 * session. Protected routes use the auth:sanctum guard, which rejects requests
 * without a valid token (fail closed).
 */

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
