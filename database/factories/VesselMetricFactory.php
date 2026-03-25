<?php

namespace Database\Factories;

use App\Models\VesselMetric;
use App\Models\Vessel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class VesselMetricFactory extends Factory
{
    protected $model = VesselMetric::class;

    public function definition()
    {
        // Período aleatorio en los últimos 12 meses en formato Y-m
        $period = Carbon::now()->subMonths(rand(0, 11))->format('Y-m');
        $createdAt = Carbon::createFromFormat('Y-m', $period)->startOfMonth();

        return [
            'period'            => $period,
            'avg_speed'         => $this->faker->randomFloat(2, 8, 25), // velocidad realista en nudos
            'fuel_consumption'  => $this->faker->randomFloat(2, 50, 500), // consumo realista en L/h
            'maintenance_count' => $this->faker->numberBetween(0, 5),
            'safety_incidents'  => $this->faker->numberBetween(0, 2),
            'created_at'        => $createdAt,
            'updated_at'        => $createdAt,
        ];
    }
}
