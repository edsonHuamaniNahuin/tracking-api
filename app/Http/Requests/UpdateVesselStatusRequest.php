<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *   schema="UpdateVesselStatusRequest",
 *   type="object",
 *   @OA\Property(property="name", type="string", maxLength=255, example="Mantenimiento")
 * )
 */
class UpdateVesselStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $status = $this->route('vessel_status');
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vessel_statuses', 'name')->ignore($status?->id),
            ],
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
