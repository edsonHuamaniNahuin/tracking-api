<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\DTO\GenericResponse;

/**
 * @OA\Schema(
 *   schema="UpdateProfileRequest",
 *   type="object",
 *   @OA\Property(property="name",     type="string", example="Juan Pérez"),
 *   @OA\Property(property="username", type="string", example="juanperez"),
 *   @OA\Property(property="email",    type="string", format="email", example="juan.perez@ejemplo.com"),
 *   @OA\Property(property="phone",    type="string", example="+34 123 456 789"),
 *   @OA\Property(property="bio",      type="string", example="Desarrollador Full Stack..."),
 *   @OA\Property(property="location", type="string", example="Madrid, España")
 * )
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255', 'unique:users,username,'.$this->user()->id],
            'email'    => ['sometimes', 'email', 'unique:users,email,'.$this->user()->id],
            'phone'    => ['sometimes', 'string', 'nullable'],
            'bio'      => ['sometimes', 'string', 'nullable'],
            'location' => ['sometimes', 'string', 'nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'     => 'El nombre debe ser un texto válido.',
            'name.max'        => 'El nombre no puede exceder los 255 caracteres.',
            'username.string' => 'El nombre de usuario debe ser un texto válido.',
            'username.max'    => 'El nombre de usuario no puede exceder los 255 caracteres.',
            'username.unique' => 'El nombre de usuario ya está en uso.',
            'email.email'     => 'El correo electrónico no tiene un formato válido.',
            'email.unique'    => 'Este correo electrónico ya está registrado.',
            'phone.string'    => 'El teléfono debe ser un texto válido.',
            'bio.string'      => 'La biografía debe ser un texto válido.',
            'location.string' => 'La ubicación debe ser un texto válido.',
        ];
    }

    /**
     * Si falla la validación, devolvemos un GenericResponse con el primer error.
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
