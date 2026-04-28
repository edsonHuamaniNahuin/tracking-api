<?php

namespace App\Enums;

/**
 * Slugs canónicos de los tipos de unidades rastreadas.
 *
 * Incluye tanto tipos marítimos como terrestres.
 * Reflejan los registros sembrados por VesselTypeStatusSeeder.
 *
 * Ejemplo:
 *   if ($vessel->vesselType?->slug === VesselTypeSlug::CARGO->value) { ... }
 *   VesselTypeSlug::CARGO->label() // → "Carguero"
 */
enum VesselTypeSlug: string
{
    // ── Marítimos ──────────────────────────────────────────────────────────
    case CARGO     = 'carguero';
    case OIL       = 'petrolero';
    case PASSENGER = 'pasajeros';
    case FISHING   = 'pesquero';
    case TUG       = 'remolcador';
    case OTHER     = 'otros';

    // ── Terrestres ─────────────────────────────────────────────────────────
    case BUS_INTERPROVINCIAL = 'bus-interprovincial';
    case BUS_URBANO          = 'bus-urbano';
    case TRUCK               = 'camion';
    case TAXI                = 'taxi';
    case MOTORCYCLE          = 'motocicleta';
    case OTHER_TERRESTRIAL   = 'otros-terrestre';

    /** Nombre legible del tipo (coincide con el campo `name` en BD). */
    public function label(): string
    {
        return match ($this) {
            self::CARGO              => 'Carguero',
            self::OIL                => 'Petrolero',
            self::PASSENGER          => 'Pasajeros',
            self::FISHING            => 'Pesquero',
            self::TUG                => 'Remolcador',
            self::OTHER              => 'Otros',
            self::BUS_INTERPROVINCIAL => 'Bus Interprovincial',
            self::BUS_URBANO         => 'Bus Urbano',
            self::TRUCK              => 'Camión',
            self::TAXI               => 'Taxi',
            self::MOTORCYCLE         => 'Motocicleta',
            self::OTHER_TERRESTRIAL  => 'Otros (Terrestre)',
        };
    }

    /** Categoría del tipo: 'maritime' o 'terrestrial'. */
    public function category(): string
    {
        return match ($this) {
            self::CARGO, self::OIL, self::PASSENGER,
            self::FISHING, self::TUG, self::OTHER => 'maritime',
            default                               => 'terrestrial',
        };
    }

    /** Icono sugerido (para uso en frontend o notificaciones). */
    public function icon(): string
    {
        return match ($this) {
            self::CARGO              => '🚢',
            self::OIL                => '🛢️',
            self::PASSENGER          => '⛴️',
            self::FISHING            => '🎣',
            self::TUG                => '⚓',
            self::OTHER              => '🚤',
            self::BUS_INTERPROVINCIAL => '🚌',
            self::BUS_URBANO         => '🚍',
            self::TRUCK              => '🚛',
            self::TAXI               => '🚕',
            self::MOTORCYCLE         => '🏍️',
            self::OTHER_TERRESTRIAL  => '🚗',
        };
    }
}
