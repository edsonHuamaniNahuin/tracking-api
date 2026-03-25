<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *   schema="UpdateVesselTypeRequest",
 *   type="object",
 *   @OA\Property(property="name", type="string", maxLength=255, example="Pesquero Modificado")
 * )
 */
class UpdateVesselTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // obtengo la instancia de VesselType que viene por ruta
        $type = $this->route('vessel_type');
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vessel_types', 'name')->ignore($type?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo "name" es obligatorio.',
            'name.unique'   => 'Ya existe un tipo con ese nombre.',
            'name.max'      => 'El nombre no debe exceder 255 caracteres.',
        ];
    }
}
