<?php
// app/Http/Middleware/ApiResponseMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseMiddleware
{
    /**
     * Envuelve todas las respuestas JSON en:
     * {
     *   "status": <HTTP code>,
     *   "message": "<mensaje>",
     *   "data": <payload original>
     * }
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo procesar respuestas JSON
        if (! str_contains($response->headers->get('Content-Type'), 'application/json')) {
            return $response;
        }

        $original = json_decode($response->getContent(), true);

        // Si ya está envuelto (tiene “status”), saltar
        if (isset($original['status'])) {
            return $response;
        }

        $status = $response->getStatusCode();

        $wrapped = [
            'status'  => $status,
            'message' => $original['message'] ?? $this->defaultMessage($status),
            'data'    => $original,
        ];

        return response()->json($wrapped, $status);
    }

    protected function defaultMessage(int $status): string
    {
        return match ($status) {
            200 => 'OK',
            201 => 'Recurso creado',
            400 => 'Solicitud inválida',
            401 => 'No autorizado',
            403 => 'Prohibido',
            404 => 'No encontrado',
            422 => 'Error de validación',
            500 => 'Error interno del servidor',
            default => '',
        };
    }
}
