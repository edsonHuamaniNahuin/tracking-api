<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;




/** @OA\Schema(
 *   schema="UpdatePreferencesRequest",
 *   type="object",
 *   @OA\Property(property="newsletter_subscribed", type="boolean", example=true),
 *   @OA\Property(property="public_profile", type="boolean", example=false),
 *   @OA\Property(property="show_online_status", type="boolean", example=true)
 * )
 */
class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         return [
            'newsletter_subscribed' => ['sometimes','boolean'],
            'public_profile'        => ['sometimes','boolean'],
            'show_online_status'    => ['sometimes','boolean'],
        ];
    }
}
