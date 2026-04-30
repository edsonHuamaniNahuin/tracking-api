<?php

namespace App\Events;

use App\Models\Tracking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se dispara cada vez que el microcontrolador (o API) guarda
 * un nuevo registro de posición GPS.
 *
 * Canal: private-vessel.{vessel_id}.tracking
 * Sólo puede suscribirse el dueño del barco o un Administrador
 * (la autorización está en routes/channels.php).
 *
 * Usa ShouldBroadcastNow para envío síncro sin necesidad de un worker
 * de colas en desarrollo. En producción puedes cambiar a ShouldBroadcast
 * y procesar con `php artisan queue:work`.
 */
class TrackingCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Tracking $tracking,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("vessel.{$this->tracking->vessel_id}.tracking");
    }

    /**
     * Nombre del evento en el cliente (snake_case por convención JS).
     */
    public function broadcastAs(): string
    {
        return 'tracking.created';
    }

    /**
     * Sólo se envía la información mínima necesaria para actualizar
     * el mapa sin una llamada REST adicional.
     */
    public function broadcastWith(): array
    {
        return [
            'id'         => $this->tracking->id,
            'vessel_id'  => $this->tracking->vessel_id,
            'latitude'   => (float) $this->tracking->latitude,
            'longitude'  => (float) $this->tracking->longitude,
            'satellites' => $this->tracking->satellites,
            'hdop'       => $this->tracking->hdop ? (float) $this->tracking->hdop : null,
            'tracked_at' => $this->tracking->tracked_at?->toISOString(),
            'created_at' => $this->tracking->created_at?->toISOString(),
        ];
    }
}
