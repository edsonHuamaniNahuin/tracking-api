<?php

namespace App\DTO;

/**
 * Transporta los datos de telemetría desde el request HTTP
 * hasta el Job y los Services. Desacopla el request de la lógica de negocio.
 *
 * Campos opcionales son nullable; la lógica de alerta
 * sólo se ejecuta si los valores están presentes.
 */
final class TelemetryData
{
    public function __construct(
        public readonly int     $vesselId,
        public readonly float   $lat,
        public readonly float   $lng,
        public readonly float   $speed,           // SOG en nudos
        public readonly ?float  $course,          // COG en grados
        public readonly ?float  $fuelLevel,       // % combustible
        public readonly ?int    $rpm,
        public readonly ?float  $voltage,
        public readonly ?array  $rawData,
        public readonly string  $recordedAt,      // ISO 8601 del dispositivo
    ) {}

    /**
     * Crea el DTO a partir del array validado por el FormRequest.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            vesselId:   (int)   $data['vessel_id'],
            lat:        (float) $data['lat'],
            lng:        (float) $data['lng'],
            speed:      (float) ($data['speed']      ?? 0.0),
            course:     isset($data['course'])     ? (float) $data['course']     : null,
            fuelLevel:  isset($data['fuel_level'])  ? (float) $data['fuel_level'] : null,
            rpm:        isset($data['rpm'])         ? (int)   $data['rpm']        : null,
            voltage:    isset($data['voltage'])     ? (float) $data['voltage']    : null,
            rawData:    $data['raw_data']           ?? null,
            recordedAt: $data['recorded_at']        ?? now()->toIso8601String(),
        );
    }
}
