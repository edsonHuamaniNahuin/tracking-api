<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de inteligencia marina.
 *
 * Provee:
 *  - Datos meteorológicos marítimos via StormGlass API (caché 30 min)
 *  - Optimización de rutas via Searoutes API (caché inmutable por ruta)
 *
 * Diseñado para escala horizontal:
 *  - Toda la caché se apoya en Redis (driver `redis`)
 *  - Los métodos son stateless y seguros para múltiples workers
 *  - Fallos de APIs externas no bloquean el flujo principal (soft degradation)
 */
class MarineIntelligenceService
{
    private const STORMGLASS_URL  = 'https://api.stormglass.io/v2';
    private const SEAROUTES_URL   = 'https://api.searoutes.com/route/v2';
    private const WEATHER_TTL_MIN = 30;

    /**
     * Parámetros meteorológicos a solicitar a StormGlass.
     * Referencia: https://docs.stormglass.io/#sources
     */
    private const WEATHER_PARAMS = [
        'waveHeight', 'wavePeriod', 'waveDirection',
        'windSpeed', 'windDirection',
        'currentSpeed', 'currentDirection',
        'visibility', 'precipitation',
        'waterTemperature', 'airTemperature',
    ];

    // ─── Meteorología ─────────────────────────────────────────────────────────

    /**
     * Obtiene el pronóstico meteorológico para una posición.
     * Caché: 30 minutos (los datos meteorológicos no cambian tan rápido).
     *
     * @param  float  $lat  Latitud decimal
     * @param  float  $lng  Longitud decimal
     * @param  int    $hours  Horas de pronóstico hacia adelante (max 168 = 7 días)
     * @return array<string, mixed>
     */
    public function getWeatherForecast(float $lat, float $lng, int $hours = 24): array
    {
        $cacheKey = sprintf('marine:weather:%s:%s:%d', round($lat, 2), round($lng, 2), $hours);

        return Cache::remember($cacheKey, now()->addMinutes(self::WEATHER_TTL_MIN), function () use ($lat, $lng, $hours) {
            return $this->fetchStormGlass($lat, $lng, $hours);
        });
    }

    /**
     * Obtiene el tiempo actual (solo la hora más cercana al ahora).
     */
    public function getCurrentWeather(float $lat, float $lng): array
    {
        $forecast = $this->getWeatherForecast($lat, $lng, 1);

        return $forecast['hourly'][0] ?? $forecast;
    }

    /**
     * Realiza la llamada HTTP a StormGlass.
     * En caso de fallo devuelve array vacío con `degraded: true`
     * para que el sistema principal siga funcionando.
     */
    private function fetchStormGlass(float $lat, float $lng, int $hours): array
    {
        $apiKey = config('services.stormglass.key');

        if (empty($apiKey)) {
            Log::channel('maritime')->warning('StormGlass API key no configurada');
            return ['degraded' => true, 'reason' => 'api_key_missing'];
        }

        $end  = now()->addHours($hours)->toIso8601String();
        $params = implode(',', self::WEATHER_PARAMS);

        try {
            $response = Http::withHeaders(['Authorization' => $apiKey])
                ->timeout(10)
                ->get(self::STORMGLASS_URL . '/weather/point', [
                    'lat'    => $lat,
                    'lng'    => $lng,
                    'params' => $params,
                    'end'    => $end,
                    'source' => 'sg',
                ]);

            if ($response->failed()) {
                Log::channel('maritime')->warning('StormGlass API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['degraded' => true, 'reason' => 'api_error', 'status' => $response->status()];
            }

            $data = $response->json();

            return [
                'degraded' => false,
                'hourly'   => $this->normalizeStormGlassHours($data['hours'] ?? []),
                'meta'     => $data['meta'] ?? [],
            ];

        } catch (\Throwable $e) {
            Log::channel('maritime')->error('StormGlass HTTP exception', ['error' => $e->getMessage()]);
            return ['degraded' => true, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }

    /**
     * Normaliza la respuesta de StormGlass: extrae el valor `sg` de cada parámetro.
     * Formato original: { "waveHeight": { "sg": 1.2, "noaa": 1.0 } }
     * Formato normalizado: { "waveHeight": 1.2 }
     *
     * @param  array<int, array<string, mixed>>  $hours
     * @return array<int, array<string, mixed>>
     */
    private function normalizeStormGlassHours(array $hours): array
    {
        return array_map(function (array $hour) {
            $normalized = ['time' => $hour['time'] ?? null];
            foreach (self::WEATHER_PARAMS as $param) {
                if (isset($hour[$param])) {
                    $normalized[$param] = $hour[$param]['sg']
                        ?? $hour[$param]['noaa']
                        ?? $hour[$param]['icon']
                        ?? null;
                }
            }
            return $normalized;
        }, $hours);
    }

    // ─── Optimización de rutas ─────────────────────────────────────────────────

    /**
     * Calcula la ruta marítima óptima entre dos puntos.
     *
     * Caché: indefinida (`rememberForever`) porque una ruta entre puerto A
     * y puerto B no cambia (geodesia). Si la API está caída, retorna
     * la ruta de círculo máximo calculada localmente.
     *
     * @param  float  $fromLat   Latitud origen
     * @param  float  $fromLng   Longitud origen
     * @param  float  $toLat     Latitud destino
     * @param  float  $toLng     Longitud destino
     * @param  float  $speedKnots  Velocidad del barco en nudos
     * @return array<string, mixed>
     */
    public function calculateOptimalRoute(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        float $speedKnots = 10.0,
    ): array {
        $cacheKey = sprintf(
            'marine:route:%s',
            md5("{$fromLat},{$fromLng},{$toLat},{$toLng},{$speedKnots}")
        );

        return Cache::rememberForever($cacheKey, function () use ($fromLat, $fromLng, $toLat, $toLng, $speedKnots) {
            return $this->fetchSearoutes($fromLat, $fromLng, $toLat, $toLng, $speedKnots);
        });
    }

    /**
     * Invalida la caché de una ruta específica.
     * Útil cuando el usuario modifica manualmente la ruta.
     */
    public function invalidateRoute(float $fromLat, float $fromLng, float $toLat, float $toLng, float $speedKnots = 10.0): void
    {
        $cacheKey = sprintf(
            'marine:route:%s',
            md5("{$fromLat},{$fromLng},{$toLat},{$toLng},{$speedKnots}")
        );
        Cache::forget($cacheKey);
    }

    /**
     * Realiza la llamada HTTP a Searoutes.
     * Fallback: si la API falla, calcula una línea recta (orthodrome).
     */
    private function fetchSearoutes(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        float $speedKnots,
    ): array {
        $apiKey = config('services.searoutes.key');

        if (empty($apiKey)) {
            Log::channel('maritime')->warning('Searoutes API key no configurada — usando fallback ortodrómica');
            return $this->orthodromeFallback($fromLat, $fromLng, $toLat, $toLng);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key'    => $apiKey,
                'Accept'       => 'application/json',
            ])
                ->timeout(15)
                ->get(self::SEAROUTES_URL . "/sea/{$fromLng},{$fromLat}/{$toLng},{$toLat}", [
                    'speed'   => $speedKnots,
                    'panama'  => true,
                    'suez'    => true,
                ]);

            if ($response->failed()) {
                Log::channel('maritime')->warning('Searoutes API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->orthodromeFallback($fromLat, $fromLng, $toLat, $toLng);
            }

            $data = $response->json();
            $feature = $data['features'][0] ?? [];

            return [
                'degraded'         => false,
                'source'           => 'searoutes',
                'distance_nm'      => ($feature['properties']['distance'] ?? null)
                    ? round(($feature['properties']['distance'] / 1852), 2)  // metros → millas náuticas
                    : null,
                'duration_hours'   => $feature['properties']['duration'] ?? null,
                'waypoints'        => $this->extractCoordinates($feature['geometry']['coordinates'] ?? []),
                'co2_kg'           => $feature['properties']['co2Emission'] ?? null,
                'raw'              => $feature,
            ];

        } catch (\Throwable $e) {
            Log::channel('maritime')->error('Searoutes HTTP exception', ['error' => $e->getMessage()]);
            return $this->orthodromeFallback($fromLat, $fromLng, $toLat, $toLng);
        }
    }

    /**
     * Fallback: ruta esferoidal de círculo máximo (orthodrome).
     * No sigue rutas marítimas, pero es mejor que nada.
     */
    private function orthodromeFallback(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $distanceNm = $this->haversineNm($fromLat, $fromLng, $toLat, $toLng);

        return [
            'degraded'     => true,
            'source'       => 'orthodrome_fallback',
            'distance_nm'  => $distanceNm,
            'waypoints'    => [
                ['lat' => $fromLat, 'lng' => $fromLng],
                ['lat' => $toLat,   'lng' => $toLng],
            ],
        ];
    }

    /**
     * Convierte array de coordenadas GeoJSON [lng, lat] a [lat, lng].
     *
     * @param  array<int, array{0: float, 1: float}>  $coordinates
     * @return array<int, array{lat: float, lng: float}>
     */
    private function extractCoordinates(array $coordinates): array
    {
        return array_map(fn(array $c) => ['lat' => $c[1], 'lng' => $c[0]], $coordinates);
    }

    /**
     * Distancia Haversine entre dos puntos en millas náuticas.
     */
    private function haversineNm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R  = 3440.065; // radio de la Tierra en millas náuticas
        $d1 = deg2rad($lat2 - $lat1);
        $d2 = deg2rad($lng2 - $lng1);

        $a = sin($d1 / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($d2 / 2) ** 2;

        return round(2 * $R * asin(sqrt($a)), 2);
    }
}
