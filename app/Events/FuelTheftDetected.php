<?php

namespace App\Events;

use App\Models\VesselTelemetry;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se dispara cuando se detecta consumo de combustible
 * con el motor apagado (posible robo de combustible).
 *
 * Implementa ShouldBroadcast para notificar al frontend
 * en tiempo real vía Laravel Echo / WebSockets.
 */
class FuelTheftDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly VesselTelemetry $telemetry,
        public readonly float           $previousFuelLevel,
        public readonly float           $currentFuelLevel,
        public readonly float           $dropPercentage,
    ) {}

    /**
     * Canal privado por vessel_id para que solo
     * el dueño del barco reciba la alerta.
     */
    public function broadcastOn(): Channel
    {
        return new Channel("vessel.{$this->telemetry->vessel_id}.alerts");
    }

    public function broadcastAs(): string
    {
        return 'fuel.theft.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'vessel_id'      => $this->telemetry->vessel_id,
            'lat'            => $this->telemetry->lat,
            'lng'            => $this->telemetry->lng,
            'previous_fuel'  => $this->previousFuelLevel,
            'current_fuel'   => $this->currentFuelLevel,
            'drop'           => $this->dropPercentage,
            'detected_at'    => $this->telemetry->recorded_at->toIso8601String(),
        ];
    }
}
