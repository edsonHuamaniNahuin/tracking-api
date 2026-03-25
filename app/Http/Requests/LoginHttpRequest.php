<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="LoginHttpRequestSchema",
 *   type="object",
 *   required={"email","password"},
 *   @OA\Property(property="email",    type="string", format="email",    example="user@example.com"),
 *   @OA\Property(property="password", type="string", format="password", example="secret123")
 * )
 */
class LoginHttpRequest extends FormRequest
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
            'email'    => ['required','email'],
            'password' => ['required','string','min:6'],
        ];
    }
}
