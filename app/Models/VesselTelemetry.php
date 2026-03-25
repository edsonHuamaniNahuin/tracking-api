<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa un ping de telemetría emitido por el microcontrolador
 * a bordo de la embarcación.
 *
 * Alta frecuencia de INSERT — no usar SoftDeletes para evitar
 * overhead en consultas de rango horario.
 */
class VesselTelemetry extends Model
{
    use HasFactory;

    protected $table = 'vessel_telemetry';

    protected $fillable = [
        'vessel_id',
        'lat',
        'lng',
        'speed',
        'course',
        'fuel_level',
        'rpm',
        'voltage',
        'raw_data',
        'recorded_at',
    ];

    protected $casts = [
        'lat'         => 'decimal:7',
        'lng'         => 'decimal:7',
        'speed'       => 'decimal:2',
        'course'      => 'decimal:2',
        'fuel_level'  => 'decimal:2',
        'voltage'     => 'decimal:2',
        'raw_data'    => 'array',
        'recorded_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    /**
     * Filtra los últimos N pings de una embarcación.
     * Usado en caché para la posición actual.
     */
    public function scopeLatestForVessel($query, int $vesselId, int $limit = 1)
    {
        return $query->where('vessel_id', $vesselId)
                     ->orderByDesc('recorded_at')
                     ->limit($limit);
    }

    /**
     * Rango temporal.
     */
    public function scopeInRange($query, string $from, string $to)
    {
        return $query->whereBetween('recorded_at', [$from, $to]);
    }

    /**
     * Solo registros con motor apagado.
     */
    public function scopeEngineOff($query)
    {
        return $query->where('rpm', 0)->orWhereNull('rpm');
    }
}
