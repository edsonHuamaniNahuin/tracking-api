<?php

namespace App\Listeners;

use App\Events\FuelTheftDetected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\FuelTheftAlertNotification;

/**
 * Escucha el evento FuelTheftDetected y:
 *  1. Registra en el log `maritime`
 *  2. Notifica al usuario dueño de la embarcación
 *
 * Implementa ShouldQueue para no bloquear el ciclo de request/response.
 */
class HandleFuelTheftAlert implements ShouldQueue
{
    public string $queue = 'alerts';

    public function handle(FuelTheftDetected $event): void
    {
        $telemetry = $event->telemetry;

        Log::channel('maritime')->warning('Posible robo de combustible detectado', [
            'vessel_id'     => $telemetry->vessel_id,
            'lat'           => $telemetry->lat,
            'lng'           => $telemetry->lng,
            'fuel_before'   => $event->previousFuelLevel,
            'fuel_after'    => $event->currentFuelLevel,
            'drop_pct'      => $event->dropPercentage,
            'recorded_at'   => $telemetry->recorded_at,
        ]);

        // Notificar al propietario de la embarcación
        $vessel = $telemetry->vessel()->with('user')->first();
        if ($vessel?->user) {
            $vessel->user->notify(new FuelTheftAlertNotification($event));
        }
    }

    public function failed(FuelTheftDetected $event, \Throwable $exception): void
    {
        Log::channel('maritime')->error('Fallo al procesar alerta de robo de combustible', [
            'vessel_id' => $event->telemetry->vessel_id,
            'error'     => $exception->getMessage(),
        ]);
    }
}
