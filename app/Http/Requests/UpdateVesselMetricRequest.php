<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVesselMetricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el Service/Policy validarán autorización
    }

    public function rules(): array
    {
        $metricId = $this->route('metric'); // parámetro {metric}

        return [
            'vessel_id'         => ['sometimes','required','integer','exists:vessels,id'],
            'period'            => [
                'sometimes','required','date',
                // Garantizar unicidad si editas el mismo vessel-period:
                Rule::unique('vessel_metrics','period')
                    ->where(fn($query) => $query->where('vessel_id', $this->input('vessel_id', $this->route('vessel_id') ?? null)))
                    ->ignore($metricId)
            ],
            'avg_speed'         => ['sometimes','nullable','numeric','min:0'],
            'fuel_consumption'  => ['sometimes','nullable','numeric','min:0'],
            'maintenance_count' => ['sometimes','nullable','integer','min:0'],
            'safety_incidents'  => ['sometimes','nullable','integer','min:0'],
        ];
    }
}
