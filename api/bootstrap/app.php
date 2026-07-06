<?php

use App\Exceptions\IllegalStatusTransitionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // A guarded status transition outside the allowed-transition map is a
        // lifecycle conflict, not a server fault: render it as 409 on the API so
        // the durable-write path reports the conflict rather than a 500
        // [docs/conventions.md Status transitions, API error responses — 409].
        $exceptions->render(function (IllegalStatusTransitionException $exception, Request $request): ?JsonResponse {
            if ($request->is('api/*')) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }

            return null;
        });

        // A missing route-bound record must read as a clean 404 that does not leak
        // the model class or id — the framework default ("No query results for
        // model [App\Models\Request] 10") would reveal internals. The router has
        // already wrapped the binding's ModelNotFoundException in a
        // NotFoundHttpException by the time this runs, so match that and detect the
        // binding case via its previous exception; controllers that report an
        // out-of-scope record as not found abort with their own message (no
        // previous) and are left untouched
        // [02_business-rules.md BR-016; docs/conventions.md API error responses — 404].
        $exceptions->render(function (NotFoundHttpException $exception, Request $request): ?JsonResponse {
            if ($request->is('api/*') && $exception->getPrevious() instanceof ModelNotFoundException) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }

            return null;
        });
    })->create();
