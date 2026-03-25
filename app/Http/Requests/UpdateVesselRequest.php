<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *   schema="UpdateVesselRequest",
 *   type="object",
 *   @OA\Property(property="name", type="string", maxLength=255, example="Mi Barco Modificado"),
 *   @OA\Property(property="imo",  type="string", maxLength=50, example="IMO654321")
 * )
 */
class UpdateVesselRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Vessel|null $vessel */
        $vessel = $this->route('vessel');

        return [
            'name' => ['sometimes','required','string','max:255'],
            'imo'  => ['sometimes','nullable','string','max:50',
                Rule::unique('vessels', 'imo')->ignore($vessel),
            ],
            'vessel_type_id'   => ['sometimes', 'required', 'integer', 'exists:vessel_types,id'],
            'vessel_status_id' => ['sometimes', 'required', 'integer', 'exists:vessel_statuses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            // Mensajes para el campo 'name'
            'name.required' => 'El campo Nombre es obligatorio cuando se quiere actualizar.',
            'name.string'   => 'El Nombre debe ser una cadena de texto.',
            'name.max'      => 'El Nombre no puede superar los 255 caracteres.',

            // Mensajes para el campo 'imo'
            'imo.string'      => 'El IMO debe ser una cadena de texto.',
            'imo.max'         => 'El IMO no puede superar los 50 caracteres.',
            'imo.unique'      => 'El IMO ya está registrado en otra embarcación.',
        ];
    }
}
