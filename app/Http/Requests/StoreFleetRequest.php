<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFleetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorización via Gate en FleetService
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color'       => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la flota es obligatorio.',
            'name.max'      => 'El nombre no puede superar 100 caracteres.',
            'color.regex'   => 'El color debe ser un valor HEX válido (ej: #3B82F6).',
        ];
    }
}
