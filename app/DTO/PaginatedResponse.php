<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema(
 *   schema="PaginatedResponse",
 *   type="object",
 *   @OA\Property(property="status",  type="integer", example=200),
 *   @OA\Property(property="message", type="string",  example="Operación exitosa"),
 *   @OA\Property(property="data",    type="array",   @OA\Items()),
 *   @OA\Property(
 *     property="meta",
 *     type="object",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page",     type="integer", example=15),
 *     @OA\Property(property="total",        type="integer", example=100),
 *     @OA\Property(property="last_page",    type="integer", example=7),
 *     @OA\Property(property="from",         type="integer", example=1),
 *     @OA\Property(property="to",           type="integer", example=15)
 *   )
 * )
 */
class PaginatedResponse implements Responsable
{
    /**
     * @param  array<int, mixed>  $items
     * @param  array{current_page:int,per_page:int,total:int,last_page:int,from:int,to:int}  $meta
     */
    public function __construct(
        public readonly array      $items,
        public readonly array      $meta,
        public readonly int        $status  = Response::HTTP_OK,
        public readonly string     $message = 'Operación exitosa'
    ) {}

    public function toResponse($request)
    {
        return response()->json([
            'status'  => $this->status,
            'message' => $this->message,
            'data'    => $this->items,
            'meta'    => $this->meta,
        ], $this->status);
    }
}
