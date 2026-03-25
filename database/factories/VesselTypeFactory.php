<?php

namespace Database\Factories;

use App\Models\VesselType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VesselTypeFactory extends Factory
{
    protected $model = VesselType::class;

    public function definition()
    {
        $name = $this->faker->unique()->randomElement([
            'Carga','Pasajeros','Pesquero','Remolcador','Yate'
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
