<?php

namespace App\DTO;

use App\Models\VesselStatus;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema(
 *   schema="VesselStatusResponse",
 *   type="object",
 *   @OA\Property(property="status",  type="integer", example=200),
 *   @OA\Property(property="message", type="string",  example="Estado creado"),
 *   @OA\Property(
 *     property="data",
 *     type="object",
 *     @OA\Property(property="id",   type="integer", example=1),
 *     @OA\Property(property="name", type="string",  example="Activa"),
 *     @OA\Property(property="slug", type="string",  example="activa")
 *   )
 * )
 */
class VesselStatusResponse implements Responsable
{
    public function __construct(
        public readonly ?VesselStatus $statusModel,
        public readonly int           $status  = Response::HTTP_OK,
        public readonly string        $message = 'Operación exitosa'
    ) {}

    public function toResponse($request)
    {
        return response()->json([
            'status'  => $this->status,
            'message' => $this->message,
            'data'    => $this->statusModel?->only(['id','name','slug']),
        ], $this->status);
    }
}
