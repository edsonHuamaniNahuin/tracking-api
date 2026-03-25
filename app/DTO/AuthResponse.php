<?php

namespace App\DTO;

use App\Models\User;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema(
 *   schema="AuthResponse",
 *   type="object",
 *   @OA\Property(property="status",  type="integer", example=200),
 *   @OA\Property(property="message", type="string",  example="Autenticación exitosa"),
 *   @OA\Property(property="data",    type="object",
 *     @OA\Property(property="user",        type="object",
 *       @OA\Property(property="id",       type="integer", example=1),
 *       @OA\Property(property="email",    type="string",  example="user@example.com"),
 *       @OA\Property(property="name",     type="string",  example="John Doe"),
 *       @OA\Property(property="username", type="string",  example="johnd"),
 *       @OA\Property(property="photoUrl", type="string",  example="https://…"),
 *     ),
 *     @OA\Property(property="access_token", type="string", example="jwt.token.here"),
 *     @OA\Property(property="token_type",   type="string", example="bearer"),
 *     @OA\Property(property="expires_in",   type="integer", example=3600),
 *   )
 * )
 */
class AuthResponse implements Responsable
{
    public function __construct(
        public readonly array|null $data,
        /** HTTP status code */
        public readonly int $status  = Response::HTTP_OK,
        /** Mensaje legible */
        public readonly string $message = 'OK',
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
