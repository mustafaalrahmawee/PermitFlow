<?php

use App\Http\Controllers\Admin\OrganizationSettingsController;
use App\Http\Controllers\Admin\RequestAssignmentController;
use App\Http\Controllers\Admin\RequestCategoryController as AdminRequestCategoryController;
use App\Http\Controllers\Admin\UserAccountController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\RequestCategoryController;
use App\Http\Controllers\RequestController;
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

    /*
     * UC-11 — administrator maintenance of request categories. The
     * `manage-categories` gate is administrator-only and fails closed for
     * inactive or non-admin actors (403) [BR-012; docs/conventions.md
     * Authorization].
     */
    Route::middleware('can:manage-categories')
        ->prefix('admin')
        ->group(function () {
            Route::get('/request-categories', [AdminRequestCategoryController::class, 'index']);
            Route::post('/request-categories', [AdminRequestCategoryController::class, 'store']);
            Route::get('/request-categories/{requestCategory}', [AdminRequestCategoryController::class, 'show']);
            Route::patch('/request-categories/{requestCategory}', [AdminRequestCategoryController::class, 'update']);
            Route::delete('/request-categories/{requestCategory}', [AdminRequestCategoryController::class, 'destroy']);
        });

    /*
     * UC-12 — administrator maintenance of the single organization-settings
     * record. The `manage-settings` gate is administrator-only and fails closed
     * for inactive or non-admin actors (403) [BR-014; docs/conventions.md
     * Authorization]. The record is a seeded singleton, so the seam is a GET/PUT
     * pair with no id, store, or destroy.
     */
    Route::middleware('can:manage-settings')
        ->prefix('admin')
        ->group(function () {
            Route::get('/organization-settings', [OrganizationSettingsController::class, 'show']);
            Route::put('/organization-settings', [OrganizationSettingsController::class, 'update']);
        });

    /*
     * UC-02 — a citizen files and submits a permit request. Category selection is
     * a plain authenticated read of active categories; request filing, editing,
     * document attachment, and submission are request-scoped (owner) with
     * ownership and status enforced by policies in the controllers. An
     * out-of-scope record reads as 404, not 403 [BR-016; docs/conventions.md
     * Authorization].
     */
    Route::get('/request-categories', [RequestCategoryController::class, 'index']);

    /*
     * UC-03 — a citizen tracks the progress of their own requests. Both reads are
     * request-scoped (owner): the list returns only requests the caller owns, and
     * an out-of-scope detail read is reported as 404, not 403, so existence is not
     * revealed [03_use-cases.md UC-03; BR-016; docs/conventions.md Authorization].
     */
    Route::get('/requests', [RequestController::class, 'index']);
    Route::get('/requests/{request}', [RequestController::class, 'show']);

    Route::post('/requests', [RequestController::class, 'store']);
    Route::patch('/requests/{request}', [RequestController::class, 'update']);
    Route::post('/requests/{request}/documents', [DocumentController::class, 'store']);
    Route::post('/requests/{request}/submit', [RequestController::class, 'submit']);

    /*
     * UC-06 — the responsible staff member starts reviewing an assigned request.
     * The shared `/requests` list and `/requests/{request}` detail seams already
     * scope reads to the responsible staff member; start-review is request-scoped
     * with `RequestPolicy@review` (responsible staff only) enforced in the
     * controller, and moves the request Submitted → In Review through the status
     * guard [03_use-cases.md UC-06; BR-009; docs/conventions.md Authorization,
     * Status transitions].
     */
    Route::post('/requests/{request}/start-review', [RequestController::class, 'startReview']);

    /*
     * UC-05 — administrator assigns or reassigns a submitted/active request to a
     * responsible staff member. All three seams are administrator-only through the
     * `assign-requests` gate, which fails closed for inactive or non-admin actors
     * (403, ext 5a) [BR-010; docs/conventions.md Authorization]. The worklist and
     * assignable-staff reads live under the `admin` prefix; the assignment write
     * targets a specific request by id.
     */
    Route::middleware('can:assign-requests')->group(function () {
        Route::get('/admin/requests', [RequestAssignmentController::class, 'index']);
        Route::get('/admin/assignable-staff', [RequestAssignmentController::class, 'assignableStaff']);
        Route::put('/requests/{request}/assignment', [RequestAssignmentController::class, 'update']);
    });
});
