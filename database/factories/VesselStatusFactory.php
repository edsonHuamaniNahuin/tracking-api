<?php

namespace Database\Factories;

use App\Models\VesselStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VesselStatusFactory extends Factory
{
    protected $model = VesselStatus::class;

    public function definition()
    {
        $name = $this->faker->unique()->randomElement([
            'Activa','Mantenimiento','Inactiva','Con Alertas'
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
