<?php

namespace App\DTO;

use App\Models\VesselType;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema(
 *   schema="VesselTypeResponse",
 *   type="object",
 *   @OA\Property(property="status",  type="integer", example=200),
 *   @OA\Property(property="message", type="string",  example="Tipo creado"),
 *   @OA\Property(
 *     property="data",
 *     type="object",
 *     @OA\Property(property="id",   type="integer", example=1),
 *     @OA\Property(property="name", type="string",  example="Pesquero"),
 *     @OA\Property(property="slug", type="string",  example="pesquero")
 *   )
 * )
 */
class VesselTypeResponse implements Responsable
{
    public function __construct(
        public readonly ?VesselType $type,
        public readonly int         $status  = Response::HTTP_OK,
        public readonly string      $message = 'Operación exitosa'
    ) {}

    public function toResponse($request)
    {
        return response()->json([
            'status'  => $this->status,
            'message' => $this->message,
            'data'    => $this->type?->only(['id','name','slug']),
        ], $this->status);
    }
}
