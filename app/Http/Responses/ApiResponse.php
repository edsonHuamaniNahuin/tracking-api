<?php
namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;

class ApiResponse implements Responsable
{
    public function __construct(
        protected mixed  $data    = null,
        protected string $message = '',
        protected int    $status  = 200
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
