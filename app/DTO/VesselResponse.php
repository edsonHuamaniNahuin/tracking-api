<?php

namespace App\DTO;

use App\Models\Vessel;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema(
 *   schema="VesselData",
 *   type="object",
 *   @OA\Property(property="id",      type="integer", example=42),
 *   @OA\Property(property="name",    type="string",  example="Mi Barco A"),
 *   @OA\Property(property="imo",     type="string",  example="IMO123456"),
 *   @OA\Property(property="user_id", type="integer", example=7),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-02T16:45:12.000000Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-02T16:45:12.000000Z")
 * )
 *
 * @OA\Schema(
 *   schema="VesselResponse",
 *   type="object",
 *   @OA\Property(property="status",  type="integer", example=200),
 *   @OA\Property(property="message", type="string",  example="Operación exitosa"),
 *   @OA\Property(property="data",    ref="#/components/schemas/VesselData")
 * )
 */
class VesselResponse implements Responsable
{
    public function __construct(
        public readonly Vessel|array|null $data,
        public readonly int $status  = Response::HTTP_OK,
        public readonly string $message = 'OK'
    ) {}

    public function toResponse($request)
    {
        // Si $this->data es un modelo, lo convertimos a JSON; si es array/lista, igual
        return response()->json([
            'status'  => $this->status,
            'message' => $this->message,
            'data'    => $this->data,
        ], $this->status);
    }
}
