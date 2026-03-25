<?php

namespace App\Http\Middleware;

use App\Models\Vessel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeviceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Acepta el token desde el header X-Device-Token o como Bearer token
        $token = $request->header('X-Device-Token') ?? $request->bearerToken();

        if (! $token) {
            return response()->json([
                'status'  => 401,
                'message' => 'Token de dispositivo requerido.',
                'data'    => null,
            ], 401);
        }

        $vessel = Vessel::where('device_token', $token)->first();

        if (! $vessel) {
            return response()->json([
                'status'  => 401,
                'message' => 'Token de dispositivo inválido.',
                'data'    => null,
            ], 401);
        }

        // Adjunta la embarcación al request para usarla en el controlador
        $request->attributes->set('_vessel', $vessel);

        return $next($request);
    }
}
