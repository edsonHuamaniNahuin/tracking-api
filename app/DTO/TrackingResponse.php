<?php

namespace App\DTO;

use App\Models\Tracking;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema(
 *   schema="TrackingData",
 *   type="object",
 *   @OA\Property(property="id",        type="integer", example=125),
 *   @OA\Property(property="vessel_id", type="integer", example=42),
 *   @OA\Property(property="latitude",  type="number",  format="float", example=-12.0453),
 *   @OA\Property(property="longitude", type="number",  format="float", example=-77.0311),
 *   @OA\Property(property="tracked_at", type="string",  format="date-time", example="2025-06-02T16:50:00.000000Z"),
 *   @OA\Property(property="created_at", type="string",  format="date-time", example="2025-06-02T16:50:30.000000Z"),
 *   @OA\Property(property="updated_at", type="string",  format="date-time", example="2025-06-02T16:50:30.000000Z")
 * )
 *
 * @OA\Schema(
 *   schema="TrackingResponse",
 *   type="object",
 *   @OA\Property(property="status",  type="integer", example=200),
 *   @OA\Property(property="message", type="string",  example="Operación exitosa"),
 *   @OA\Property(property="data",    ref="#/components/schemas/TrackingData")
 * )
 */
class TrackingResponse implements Responsable
{
    public function __construct(
        public readonly Tracking|array|null $data,
        public readonly int $status  = Response::HTTP_OK,
        public readonly string $message = 'OK'
    ) {}

    public function toResponse($request)
    {
        return response()->json([
            'status'  => $this->status,
            'message' => $this->message,
            'data'    => $this->data,
        ], $this->status);
    }
}
