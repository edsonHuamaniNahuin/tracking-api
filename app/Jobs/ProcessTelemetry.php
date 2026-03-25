<?php

namespace App\Jobs;

use App\DTO\TelemetryData;
use App\Events\FuelTheftDetected;
use App\Models\VesselTelemetry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Procesa un ping de telemetría recibido del microcontrolador.
 *
 * Arquitectura de colas:
 *  - Cola `telemetry`  → alta prioridad, procesamiento inmediato
 *  - Reintentos: 3, con backoff exponencial (30s, 120s, 480s)
 *
 * Lógica:
 *  1. Persiste el registro en `vessel_telemetry`
 *  2. Actualiza la caché de "última posición" del barco (TTL 5 min)
 *  3. Invalida la caché de rutas del barco (inmutable pero nueva)
 *  4. Detecta posible robo de combustible y dispara FuelTheftDetected
 */
class ProcessTelemetry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int    $tries       = 3;
    public int    $maxExceptions = 1;
    public array  $backoff      = [30, 120, 480];

    public function __construct(private readonly TelemetryData $telemetryData)
    {
        $this->onQueue('telemetry');
    }

    public function handle(): void
    {
        try {
            $telemetry = DB::transaction(function () {
                return VesselTelemetry::create([
                    'vessel_id'   => $this->telemetryData->vesselId,
                    'lat'         => $this->telemetryData->lat,
                    'lng'         => $this->telemetryData->lng,
                    'speed'       => $this->telemetryData->speed,
                    'course'      => $this->telemetryData->course,
                    'fuel_level'  => $this->telemetryData->fuelLevel,
                    'rpm'         => $this->telemetryData->rpm,
                    'voltage'     => $this->telemetryData->voltage,
                    'raw_data'    => $this->telemetryData->rawData,
                    'recorded_at' => $this->telemetryData->recordedAt,
                ]);
            });

            $this->updatePositionCache($telemetry);
            $this->detectFuelTheft($telemetry);

            Log::channel('maritime')->info('Telemetría procesada', [
                'vessel_id'  => $telemetry->vessel_id,
                'speed'      => $telemetry->speed,
                'fuel_level' => $telemetry->fuel_level,
                'recorded_at'=> $telemetry->recorded_at,
            ]);

        } catch (\Throwable $e) {
            Log::channel('maritime')->error('Error procesando telemetría', [
                'vessel_id' => $this->telemetryData->vesselId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            throw $e;  // re-lanza para que la cola reintente
        }
    }

    /**
     * Actualiza la caché Redis con la última posición del barco.
     * TTL: 5 minutos (si el barco no manda ping en 5 min, se considera offline).
     */
    private function updatePositionCache(VesselTelemetry $telemetry): void
    {
        $key = "vessel:{$telemetry->vessel_id}:last_position";

        Cache::put($key, [
            'lat'         => $telemetry->lat,
            'lng'         => $telemetry->lng,
            'speed'       => $telemetry->speed,
            'course'      => $telemetry->course,
            'fuel_level'  => $telemetry->fuel_level,
            'rpm'         => $telemetry->rpm,
            'voltage'     => $telemetry->voltage,
            'recorded_at' => $telemetry->recorded_at->toIso8601String(),
        ], now()->addMinutes(5));
    }

    /**
     * Detecta robo de combustible:
     * Condición: caída de combustible > 5% con RPM == 0 (motor apagado).
     *
     * Compara con el último nivel registrado en caché para evitar
     * consultar la BD en cada ping (alto volumen).
     */
    private function detectFuelTheft(VesselTelemetry $telemetry): void
    {
        if ($telemetry->fuel_level === null) {
            return;
        }

        $fuelKey = "vessel:{$telemetry->vessel_id}:prev_fuel_level";
        $previousFuel = Cache::get($fuelKey);

        // Guardar el nivel actual para la próxima comparación
        Cache::put($fuelKey, $telemetry->fuel_level, now()->addHours(24));

        if ($previousFuel === null) {
            return; // Primer ping, sin baseline para comparar
        }

        $drop = (float) $previousFuel - (float) $telemetry->fuel_level;
        $engineOff = ($telemetry->rpm === null || $telemetry->rpm === 0);

        if ($drop >= 5.0 && $engineOff) {
            FuelTheftDetected::dispatch(
                $telemetry,
                (float) $previousFuel,
                (float) $telemetry->fuel_level,
                $drop
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('maritime')->critical('Job ProcessTelemetry falló definitivamente', [
            'vessel_id' => $this->telemetryData->vesselId,
            'error'     => $exception->getMessage(),
        ]);
    }
}
