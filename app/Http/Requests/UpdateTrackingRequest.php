<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="UpdateTrackingRequest",
 *   type="object",
 *   @OA\Property(property="latitude",  type="number", format="float", example=-12.0498),
 *   @OA\Property(property="longitude", type="number", format="float", example=-77.0289),
 *   @OA\Property(property="tracked_at", type="string", format="date-time", example="2025-06-02T16:40:00Z")
 * )
 */
class UpdateTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude'   => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude'  => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'tracked_at' => ['sometimes', 'date'],
        ];
    }
}
