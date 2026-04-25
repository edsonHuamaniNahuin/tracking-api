<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreTrackingRequest",
 *   type="object",
 *   required={"vessel_id","latitude","longitude"},
 *   @OA\Property(property="vessel_id", type="integer", example=42),
 *   @OA\Property(property="latitude",  type="number", format="float", example=-12.0453),
 *   @OA\Property(property="longitude", type="number", format="float", example=-77.0311),
 *   @OA\Property(property="tracked_at", type="string", format="date-time", example="2025-06-02T16:50:00Z")
 * )
 */
class StoreTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vessel_id'  => ['required', 'integer', 'exists:vessels,id'],
            'latitude'   => ['required', 'numeric', 'between:-90,90'],
            'longitude'  => ['required', 'numeric', 'between:-180,180'],
            'satellites' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:99'],
            'hdop'       => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99.99'],
            'tracked_at' => ['sometimes', 'date'],
        ];
    }
}
