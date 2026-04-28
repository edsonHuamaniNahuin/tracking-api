<?php

namespace App\DTO;

use App\Models\Fleet;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\Response;

class FleetResponse implements Responsable
{
    public function __construct(
        public readonly Fleet|Collection|array|null $data,
        public readonly int    $status  = Response::HTTP_OK,
        public readonly string $message = 'OK'
    ) {}

    public function toResponse($request)
    {
        $payload = $this->data instanceof Collection
            ? $this->data->values()->toArray()
            : $this->data;

        return response()->json([
            'status'  => $this->status,
            'message' => $this->message,
            'data'    => $payload,
        ], $this->status);
    }
}
