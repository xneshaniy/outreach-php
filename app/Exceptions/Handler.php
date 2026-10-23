<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
use Inertia\Inertia;

class Handler extends ExceptionHandler
{
    protected $levels = [
        //
    ];

    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'password_encrypted',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return redirect()->guest(route('login'));
        });

        $this->renderable(function (AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            return response()->view('errors.403', [], 403);
        });

        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Resource not found'], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Not found'], 404);
            }
            return response()->view('errors.404', [], 404);
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Method not allowed'], 405);
            }
            return response()->view('errors.405', [], 405);
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($this->isHttpException($e)) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                ], 500);
            }

            if (config('app.debug')) {
                return null; // Let Laravel handle it with detailed error page
            }

            return response()->view('errors.500', [], 500);
        });
    }

    protected function prepareResponse($request, Throwable $e): \Symfony\Component\HttpFoundation\Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return parent::prepareResponse($request, $e);
        }

        if (config('app.debug')) {
            return parent::prepareResponse($request, $e);
        }

        // Production error pages with Inertia
        $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;

        return Inertia::render('Error', [
            'status' => $status,
            'message' => $status === 404 ? 'Page not found' : ($status === 403 ? 'Access denied' : 'Something went wrong'),
        ])->toResponse($request)->setStatusCode($status);
    }
}