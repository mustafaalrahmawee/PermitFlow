<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
 * API routes (Sanctum stateful SPA, §5.5). The api group is made stateful in
 * bootstrap/app.php via $middleware->statefulApi(); protected routes use the
 * auth:sanctum guard, which rejects unauthenticated requests (fail closed).
 */

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
