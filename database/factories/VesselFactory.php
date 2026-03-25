<?php

namespace Database\Factories;

use App\Models\Vessel;
use App\Models\VesselStatus;
use App\Models\VesselType;
use Illuminate\Database\Eloquent\Factories\Factory;

class VesselFactory extends Factory
{
    /**
     * El modelo que corresponde a este factory.
     *
     * @var string
     */
    protected $model = Vessel::class;

    /**
     * Define el estado por defecto del modelo.
     *
     * @return array
     */
    public function definition()
    {
        // Asegurar que existan tipos y estados antes de crear un vessel
        $typeId = VesselType::inRandomOrder()->first()?->id;
        $statusId = VesselStatus::inRandomOrder()->first()?->id;

        // Si no hay tipos o estados disponibles, crearlos
        if (!$typeId) {
            $type = VesselType::create([
                'name' => 'Carguero',
                'slug' => 'carguero'
            ]);
            $typeId = $type->id;
        }

        if (!$statusId) {
            $status = VesselStatus::create([
                'name' => 'Activa',
                'slug' => 'activa'
            ]);
            $statusId = $status->id;
        }

        return [
            'name' => $this->faker->company . ' ' . $this->faker->randomElement(['Explorer', 'Navigator', 'Voyager', 'Pioneer', 'Guardian']),
            'imo'  => 'IMO' . $this->faker->unique()->numerify('#######'),
            'vessel_type_id'   => $typeId,
            'vessel_status_id' => $statusId,
        ];
    }
}
