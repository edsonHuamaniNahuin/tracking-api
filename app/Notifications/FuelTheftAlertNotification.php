<?php

namespace App\Notifications;

use App\Events\FuelTheftDetected;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notificación enviada al propietario de la embarcación
 * cuando se detecta un posible robo de combustible.
 */
class FuelTheftAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly FuelTheftDetected $event) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vessel = $this->event->telemetry->vessel;

        return (new MailMessage)
            ->subject('⚠️ Alerta: Posible robo de combustible detectado')
            ->greeting("Hola {$notifiable->name},")
            ->line("Se detectó una caída de combustible del {$this->event->dropPercentage}% en **{$vessel->name}** con el motor apagado.")
            ->line("Nivel anterior: {$this->event->previousFuelLevel}% → Nivel actual: {$this->event->currentFuelLevel}%")
            ->line("Posición: Lat {$this->event->telemetry->lat}, Lng {$this->event->telemetry->lng}")
            ->action('Ver en el mapa', url("/tracking/map?vessel={$vessel->id}"))
            ->line('Si esto fue autorizado, puedes ignorar este mensaje.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'fuel_theft',
            'vessel_id'     => $this->event->telemetry->vessel_id,
            'vessel_name'   => $this->event->telemetry->vessel->name ?? null,
            'previous_fuel' => $this->event->previousFuelLevel,
            'current_fuel'  => $this->event->currentFuelLevel,
            'drop'          => $this->event->dropPercentage,
            'lat'           => $this->event->telemetry->lat,
            'lng'           => $this->event->telemetry->lng,
            'detected_at'   => $this->event->telemetry->recorded_at,
        ];
    }
}
