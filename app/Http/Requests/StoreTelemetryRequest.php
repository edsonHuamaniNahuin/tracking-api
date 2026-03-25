<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el payload del microcontrolador en el endpoint POST /v1/telemetry.
 *
 * El dispositivo puede omitir campos de motor/combustible si no tiene
 * los sensores instalados, por eso son nullable.
 */
class StoreTelemetryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real (vessel ownership) se hace en el controller
        return true;
    }

    public function rules(): array
    {
        return [
            'vessel_id'   => ['required', 'integer', 'exists:vessels,id'],
            'lat'         => ['required', 'numeric', 'between:-90,90'],
            'lng'         => ['required', 'numeric', 'between:-180,180'],
            'speed'       => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'course'      => ['sometimes', 'numeric', 'between:0,360'],
            'fuel_level'  => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'rpm'         => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10000'],
            'voltage'     => ['sometimes', 'nullable', 'numeric', 'between:0,48'],
            'raw_data'    => ['sometimes', 'nullable', 'array'],
            'recorded_at' => ['sometimes', 'date_format:Y-m-d\TH:i:sP,Y-m-d H:i:s'],
        ];
    }

    public function messages(): array
    {
        return [
            'vessel_id.exists' => 'La embarcación indicada no existe.',
            'lat.between'      => 'Latitud debe estar entre -90 y 90.',
            'lng.between'      => 'Longitud debe estar entre -180 y 180.',
            'fuel_level.between' => 'Nivel de combustible debe estar entre 0 y 100%.',
        ];
    }
}
