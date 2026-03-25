<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema(
 *   schema="VesselTypeCollectionResponse",
 *   type="object",
 *   @OA\Property(property="status",  type="integer", example=200),
 *   @OA\Property(property="message", type="string",  example="Listado de tipos"),
 *   @OA\Property(
 *     property="data",
 *     type="array",
 *     @OA\Items(
 *       type="object",
 *       @OA\Property(property="id",   type="integer", example=1),
 *       @OA\Property(property="name", type="string",  example="Pesquero"),
 *       @OA\Property(property="slug", type="string",  example="pesquero")
 *     )
 *   )
 * )
 */
class VesselTypeCollectionResponse implements Responsable
{
    /**
     * @param  array<int, mixed>  $items
     */
    public function __construct(
        public readonly array  $items,
        public readonly int    $status  = Response::HTTP_OK,
        public readonly string $message = 'Listado de tipos'
    ) {}

    public function toResponse($request)
    {
        return response()->json([
            'status'  => $this->status,
            'message' => $this->message,
            'data'    => $this->items,
        ], $this->status);
    }
}
