<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Endpoints para el dashboard de métricas y estadísticas"
 * )
 */
class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/metrics",
     *     summary="Obtener métricas principales del dashboard",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Métricas principales obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_vessels", type="integer", example=245),
     *             @OA\Property(property="active_vessels", type="integer", example=189),
     *             @OA\Property(property="total_trackings", type="integer", example=15678),
     *             @OA\Property(property="total_users", type="integer", example=25),
     *             @OA\Property(property="vessels_in_maintenance", type="integer", example=42),
     *             @OA\Property(property="vessels_with_alerts", type="integer", example=8)
     *         )
     *     )
     * )
     */
    public function getMainMetrics(): JsonResponse
    {
        $metrics = $this->dashboardService->getMainMetrics();
        return response()->json($metrics);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/vessels-type",
     *     summary="Obtener distribución de embarcaciones por tipo",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Distribución por tipo obtenida exitosamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="type", type="string", example="Carguero"),
     *                 @OA\Property(property="count", type="integer", example=65),
     *                 @OA\Property(property="percentage", type="number", format="float", example=26.5)
     *             )
     *         )
     *     )
     * )
     */
    public function getVesselsByType(): JsonResponse
    {
        $data = $this->dashboardService->getVesselsByType();
        return response()->json($data);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/vessels-status",
     *     summary="Obtener distribución de embarcaciones por estado",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Distribución por estado obtenida exitosamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="status", type="string", example="Activa"),
     *                 @OA\Property(property="count", type="integer", example=189),
     *                 @OA\Property(property="percentage", type="number", format="float", example=77.1)
     *             )
     *         )
     *     )
     * )
     */
    public function getVesselsByStatus(): JsonResponse
    {
        $data = $this->dashboardService->getVesselsByStatus();
        return response()->json($data);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/vessel-positions",
     *     summary="Obtener últimas posiciones de embarcaciones activas",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Posiciones obtenidas exitosamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="vessel_id", type="integer", example=1),
     *                 @OA\Property(property="vessel_name", type="string", example="Atlantic Explorer 01"),
     *                 @OA\Property(property="latitude", type="number", format="float", example=-23.5505),
     *                 @OA\Property(property="longitude", type="number", format="float", example=-46.6333),
     *                 @OA\Property(property="last_update", type="string", format="datetime", example="2024-06-15T14:30:00Z")
     *             )
     *         )
     *     )
     * )
     */
    public function getVesselPositions(): JsonResponse
    {
        $positions = $this->dashboardService->getVesselPositions();
        return response()->json($positions);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/monthly-activity",
     *     summary="Obtener actividad mensual de trackings",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Actividad mensual obtenida exitosamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="month", type="string", example="2024-06"),
     *                 @OA\Property(property="trackings_count", type="integer", example=1250),
     *                 @OA\Property(property="active_vessels", type="integer", example=189)
     *             )
     *         )
     *     )
     * )
     */
    public function getMonthlyActivity(): JsonResponse
    {
        $activity = $this->dashboardService->getMonthlyActivity();
        return response()->json($activity);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/fleet-aging",
     *     summary="Obtener distribución de antigüedad de la flota",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Distribución de antigüedad obtenida exitosamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="age_range", type="string", example="0-5 años"),
     *                 @OA\Property(property="count", type="integer", example=45),
     *                 @OA\Property(property="percentage", type="number", format="float", example=18.4)
     *             )
     *         )
     *     )
     * )
     */
    public function getFleetAging(): JsonResponse
    {
        $aging = $this->dashboardService->getFleetAging();
        return response()->json($aging);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/performance-metrics",
     *     summary="Obtener métricas de rendimiento de la flota",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Métricas de rendimiento obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="avg_speed", type="number", format="float", example=18.5),
     *             @OA\Property(property="avg_fuel_consumption", type="number", format="float", example=245.7),
     *             @OA\Property(property="total_maintenance_count", type="integer", example=128),
     *             @OA\Property(property="total_safety_incidents", type="integer", example=12),
     *             @OA\Property(property="efficiency_score", type="number", format="float", example=87.3)
     *         )
     *     )
     * )
     */
    public function getPerformanceMetrics(): JsonResponse
    {
        $metrics = $this->dashboardService->getPerformanceMetrics();
        return response()->json($metrics);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/all-metrics",
     *     summary="Obtener todas las métricas del dashboard en una sola respuesta",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Todas las métricas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="main_metrics", type="object"),
     *             @OA\Property(property="vessels_by_type", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="vessels_by_status", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="vessel_positions", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="monthly_activity", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="fleet_aging", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="performance_metrics", type="object")
     *         )
     *     )
     * )
     */
    public function getAllMetrics(): JsonResponse
    {
        $allMetrics = [
            'main_metrics' => $this->dashboardService->getMainMetrics(),
            'vessels_by_type' => $this->dashboardService->getVesselsByType(),
            'vessels_by_status' => $this->dashboardService->getVesselsByStatus(),
            'vessel_positions' => $this->dashboardService->getVesselPositions(),
            'monthly_activity' => $this->dashboardService->getMonthlyActivity(),
            'fleet_aging' => $this->dashboardService->getFleetAging(),
            'performance_metrics' => $this->dashboardService->getPerformanceMetrics(),
        ];

        return response()->json($allMetrics);
    }

    // ===== MÉTODOS PARA FORMULARIOS (listado completo de opciones) =====

    /**
     * @OA\Get(
     *     path="/api/v1/vessels-types",
     *     summary="Obtener listado completo de tipos de embarcaciones para formularios",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Tipos de embarcaciones obtenidos exitosamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Carguero"),
     *                 @OA\Property(property="slug", type="string", example="carguero"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function getVesselTypesForForms(): JsonResponse
    {
        $types = $this->dashboardService->getVesselTypesForForms();
        return response()->json($types);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vessels-status",
     *     summary="Obtener listado completo de estados de embarcaciones para formularios",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estados de embarcaciones obtenidos exitosamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Activa"),
     *                 @OA\Property(property="slug", type="string", example="activa"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function getVesselStatusForForms(): JsonResponse
    {
        $statuses = $this->dashboardService->getVesselStatusForForms();
        return response()->json($statuses);
    }
}
