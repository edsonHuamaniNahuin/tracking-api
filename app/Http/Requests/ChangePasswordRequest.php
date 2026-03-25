<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\DTO\GenericResponse;

/**
 * @OA\Schema(
 *   schema="ChangePasswordRequest",
 *   type="object",
 *   required={"current_password","new_password"},
 *   @OA\Property(property="current_password", type="string", format="password", example="oldPass123"),
 *   @OA\Property(property="new_password", type="string", format="password", example="newSecret456")
 * )
 */
class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password'              => ['required', 'string'],
            'new_password'                  => ['required', 'string', 'min:8', 'confirmed'],
            'new_password_confirmation'     => ['required', 'string'],
        ];
    }

    /**
     * Mensajes de validación en español.
     */
    public function messages(): array
    {
        return [
            'current_password.required'          => 'La contraseña actual es obligatoria.',
            'new_password.required'              => 'La nueva contraseña es obligatoria.',
            'new_password.min'                   => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed'             => 'La confirmación no coincide con la nueva contraseña.',
            'new_password_confirmation.required' => 'La confirmación de la nueva contraseña es obligatoria.',
        ];
    }

    /**
     * Cuando falla la validación, lanzamos un GenericResponse en lugar
     * del 422 nativo de Laravel.
     */
    protected function failedValidation(Validator $validator): void
    {
        $firstError = $validator->errors()->first();

        $response = (new GenericResponse(
            data:    null,
            status:  422,
            message: $firstError
        ))->toResponse($this);

        throw new HttpResponseException($response);
    }
}
