<?php
namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *   title="Tracking API",
 *   version="1.0.0",
 *   description="Documentación de la Tracking API",
 *   @OA\Contact(email="soporte@tudominio.com")
 * )
 *
 * // Esquemas de petición
 * @OA\Schema(
 *   schema="LoginRequest",
 *   type="object",
 *   required={"email","password"},
 *   @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *   @OA\Property(property="password", type="string", format="password", example="secret123")
 * )
 *
 *
 *
 */
class SwaggerAnnotations
{
    // Este archivo no necesita métodos. Solo sirve para agrupar
    // los comentarios de OpenAPI en un solo sitio.
}
