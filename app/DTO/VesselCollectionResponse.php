<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema(
 *   schema="VesselCollectionResponse",
 *   type="object",
 *   @OA\Property(property="status",  type="integer", example=200),
 *   @OA\Property(property="message", type="string",  example="Lista de embarcaciones"),
 *   @OA\Property(
 *     property="data",
 *     type="array",
 *     @OA\Items(ref="#/components/schemas/VesselData")
 *   )
 * )
 */
class VesselCollectionResponse implements Responsable
{
    /**
     * @param  array<int, \App\Models\Vessel>  $items
     */
    public function __construct(
        public readonly array|null $items,
        public readonly int $status  = Response::HTTP_OK,
        public readonly string $message = 'OK'
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
