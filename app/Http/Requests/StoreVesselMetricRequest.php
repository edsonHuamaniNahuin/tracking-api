<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVesselMetricRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permitimos acá; validaremos permisos en el Service/Policy
        return true;
    }

    public function rules(): array
    {
        return [
            'vessel_id'         => ['required','integer','exists:vessels,id'],
            'period'            => ['required','date'],
            'avg_speed'         => ['nullable','numeric','min:0'],
            'fuel_consumption'  => ['nullable','numeric','min:0'],
            'maintenance_count' => ['nullable','integer','min:0'],
            'safety_incidents'  => ['nullable','integer','min:0'],
        ];
    }
}
