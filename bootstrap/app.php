<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/*
 * Some shared hosts keep environment variables from an earlier .env inside
 * the PHP-FPM worker process. Dotenv refuses to overwrite existing variables,
 * so a stale DB_DATABASE would silently win over the current .env on those
 * workers. Drop inherited database settings so .env is always authoritative.
 * Only web requests are affected: CLI processes (artisan, queue workers,
 * PHPUnit) start fresh and must keep their own environment overrides.
 */
if (PHP_SAPI !== 'cli') {
    foreach (['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $envKey) {
        putenv($envKey);
        unset($_ENV[$envKey], $_SERVER[$envKey]);
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'workspace.selected' => \App\Http\Middleware\EnsureWorkspaceSelected::class,
            'workspace.auth' => \App\Http\Middleware\WorkspaceAuthorization::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Not found'], 404);
            }

            return Inertia::render('Error', [
                'status' => 404,
                'message' => 'Page not found',
            ])->toResponse($request)->setStatusCode(404);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            return Inertia::render('Error', [
                'status' => 403,
                'message' => 'Access denied',
            ])->toResponse($request)->setStatusCode(403);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            // Validation errors, authentication redirects and HTTP exceptions
            // have their own framework handling; never swallow them into a 500.
            if ($request->expectsJson()
                || $e instanceof HttpExceptionInterface
                || $e instanceof ValidationException
                || $e instanceof AuthenticationException) {
                return null;
            }

            if (config('app.debug')) {
                return null;
            }

            return Inertia::render('Error', [
                'status' => 500,
                'message' => 'Something went wrong',
            ])->toResponse($request)->setStatusCode(500);
        });
    })
    ->create();
