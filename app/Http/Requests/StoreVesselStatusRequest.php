<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreVesselStatusRequest",
 *   type="object",
 *   required={"name"},
 *   @OA\Property(property="name", type="string", maxLength=255, example="Activa")
 * )
 */
class StoreVesselStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:vessel_statuses,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo "name" es obligatorio.',
            'name.unique'   => 'Ya existe un estado con ese nombre.',
            'name.max'      => 'El nombre no debe exceder 255 caracteres.',
        ];
    }
}
