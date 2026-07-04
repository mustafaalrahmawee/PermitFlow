<?php

use App\Http\Controllers\Admin\UserAccountController;
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

    /*
     * UC-01 — administrator maintenance of user accounts and roles. The
     * `manage-accounts` gate is administrator-only and fails closed for
     * inactive or non-admin actors (403) [BR-013; docs/conventions.md
     * Authorization].
     */
    Route::middleware('can:manage-accounts')
        ->prefix('admin')
        ->group(function () {
            Route::get('/user-accounts', [UserAccountController::class, 'index']);
            Route::post('/user-accounts', [UserAccountController::class, 'store']);
            Route::get('/user-accounts/{userAccount}', [UserAccountController::class, 'show']);
            Route::patch('/user-accounts/{userAccount}', [UserAccountController::class, 'update']);
        });
});
