<?php

namespace App\Providers;

use App\Events\FuelTheftDetected;
use App\Listeners\HandleFuelTheftAlert;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Mapa de eventos → listeners.
     *
     * @var array<class-string, array<class-string>>
     */
    protected $listen = [
        FuelTheftDetected::class => [
            HandleFuelTheftAlert::class,
        ],
    ];

    /**
     * Registra cualquier otro servicio del provider si es necesario.
     */
    public function boot(): void
    {
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false; // Registramos manualmente para mayor control
    }
}
