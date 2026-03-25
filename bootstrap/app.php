<?php

use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Middleware\ApplySystemTimezone;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
         $middleware->api(append: [
            ApplySystemTimezone::class,
            ApiResponseMiddleware::class,
        ]);
        $middleware->alias([
            'device.token' => \App\Http\Middleware\VerifyDeviceToken::class,
        ]);
    })
   ->withExceptions(function (Exceptions $exceptions) {
        //
        // Aquí registramos “renderable” para cada excepción relevante.
        // Siempre que la ruta empiece con “api/”, devolveremos JSON en lugar de HTML.
        //

        // 401: no autenticado (Auth guard o JWT)
        $exceptions->renderable(function (
            AuthenticationException | TokenExpiredException | TokenInvalidException | JWTException $e,
            $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'No estás autenticado. Por favor, inicia sesión o envía un token válido.',
                    'data'    => null,
                ], 401);
            }
        });

        // 403: acceso denegado
        $exceptions->renderable(function (
            AuthorizationException | AccessDeniedHttpException $e,
            $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 403,
                    'message' => 'No tienes permiso para realizar esta acción.',
                    'data'    => null,
                ], 403);
            }
        });

        // 404: recurso o ruta no encontrada
        $exceptions->renderable(function (
            NotFoundHttpException | ModelNotFoundException $e,
            $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'El recurso solicitado no existe.',
                    'data'    => null,
                ], 404);
            }
        });

        // 405: método no permitido
        $exceptions->renderable(function (
            MethodNotAllowedHttpException $e,
            $request
        ) {
            if ($request->is('api/*')) {
                $allowed = $e->getHeaders()['Allow'] ?? '';
                return response()->json([
                    'status'  => 405,
                    'message' => 'Método no permitido en esta ruta. Usa uno de estos: ' . $allowed,
                    'data'    => null,
                ], 405);
            }
        });

        // 422: error de validación
        $exceptions->renderable(function (
            ValidationException $e,
            $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 422,
                    'message' => 'Error de validación en los datos enviados.',
                    'data'    => $e->errors(),
                ], 422);
            }
        });

        // 500: cualquier otra excepción
        $exceptions->renderable(function (
            Throwable $e,
            $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 500,
                    'message' => 'Error interno del servidor. Intenta de nuevo más tarde.',
                    'data'    => null,
                ], 500);
            }
        });
    })->create();
