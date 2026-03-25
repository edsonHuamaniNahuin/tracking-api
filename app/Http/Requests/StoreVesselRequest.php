<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreVesselRequest",
 *   type="object",
 *   required={"name"},
 *   @OA\Property(property="name", type="string", maxLength=255, example="Mi Barco A"),
 *   @OA\Property(property="imo",  type="string", maxLength=50, example="IMO123456")
 * )
 */
class StoreVesselRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'imo'  => ['nullable', 'string', 'max:50', 'unique:vessels,imo'],
            'vessel_type_id'   => ['required', 'integer', 'exists:vessel_types,id'],
            'vessel_status_id' => ['required', 'integer', 'exists:vessel_statuses,id'],
        ];
    }
}
