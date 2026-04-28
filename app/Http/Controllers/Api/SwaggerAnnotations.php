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
 * @OA\Schema(
 *   schema="StoreVesselMetricRequest",
 *   type="object",
 *   required={"vessel_id","period"},
 *   @OA\Property(property="vessel_id",         type="integer", example=1),
 *   @OA\Property(property="period",            type="string",  format="date", example="2026-04-01"),
 *   @OA\Property(property="avg_speed",         type="number",  format="float", nullable=true, example=12.5),
 *   @OA\Property(property="fuel_consumption",  type="number",  format="float", nullable=true, example=320.0),
 *   @OA\Property(property="maintenance_count", type="integer", nullable=true, example=2),
 *   @OA\Property(property="safety_incidents",  type="integer", nullable=true, example=0)
 * )
 *
 * @OA\Schema(
 *   schema="UpdateVesselMetricRequest",
 *   type="object",
 *   @OA\Property(property="vessel_id",         type="integer", nullable=true, example=1),
 *   @OA\Property(property="period",            type="string",  format="date", nullable=true, example="2026-04-01"),
 *   @OA\Property(property="avg_speed",         type="number",  format="float", nullable=true, example=12.5),
 *   @OA\Property(property="fuel_consumption",  type="number",  format="float", nullable=true, example=320.0),
 *   @OA\Property(property="maintenance_count", type="integer", nullable=true, example=2),
 *   @OA\Property(property="safety_incidents",  type="integer", nullable=true, example=0)
 * )
 *
 */
class SwaggerAnnotations
{
    // Este archivo no necesita métodos. Solo sirve para agrupar
    // los comentarios de OpenAPI en un solo sitio.
}
