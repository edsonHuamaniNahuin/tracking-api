<?php

namespace Database\Factories;

use App\Models\Tracking;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrackingFactory extends Factory
{
    /**
     * El modelo que corresponde a este factory.
     *
     * @var string
     */
    protected $model = Tracking::class;

    /**
     * Define el estado por defecto del modelo.
     *
     * @return array
     */
    public function definition()
    {
        $trackedAt = $this->faker->dateTimeBetween('-6 months', 'now');

        return [
            'latitude'  => $this->faker->latitude(-90, 90),
            'longitude' => $this->faker->longitude(-180, 180),
            'tracked_at' => $trackedAt,
            'created_at' => $trackedAt,
            'updated_at' => $trackedAt,
        ];
    }
}
