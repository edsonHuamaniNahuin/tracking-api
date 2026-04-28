<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los parámetros de paginación.
 *
 * @OA\Schema(
 *   schema="PaginateRequest",
 *   type="object",
 *   @OA\Property(property="page",     type="integer", example=1, minimum=1),
   *   @OA\Property(property="per_page", type="integer", example=15, minimum=1, maximum=500)
 * )
 */
class PaginateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'     => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'name'     => ['nullable', 'string', 'max:255'],
            'imo'      => ['nullable', 'string', 'max:50'],
            'type_id'     => ['sometimes', 'integer', 'exists:vessel_types,id'],
            'status_id'   => ['sometimes', 'integer', 'exists:vessel_statuses,id'],
            'own_only'    => ['sometimes', 'in:0,1,true,false'],
        ];
    }


    /**
     * Devuelve el número de página, con default=1
     */
    public function page(): int
    {
        return (int) $this->input('page', 1);
    }

    /**
     * Devuelve la cantidad de ítems por página, con default=15
     */
    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
    }


    /**
     * Devuelve el filtro para 'name', o null si no vino
     */
    public function filterName(): ?string
    {
        return $this->input('name');
    }

    /**
     * Devuelve el filtro para 'imo', o null si no vino
     */
    public function filterImo(): ?string
    {
        return $this->input('imo');
    }

        public function filterTypeId(): ?int
    {
        return $this->input('type_id');
    }

    public function filterStatusId(): ?int
    {
        return $this->input('status_id');
    }

    /** Si es true, siempre filtra por user_id del usuario autenticado (ignora rol admin). */
    public function filterOwnOnly(): bool
    {
        $val = $this->input('own_only', false);
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }
}
