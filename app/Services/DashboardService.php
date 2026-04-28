<?php

namespace App\Services;

use App\Models\Vessel;
use App\Models\VesselType;
use App\Models\VesselStatus;
use App\Models\Tracking;
use App\Models\VesselMetric;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Obtener métricas principales del dashboard
     */
    public function getMainMetrics(): array
    {
        return [
            'total_vessels' => Vessel::count(),
            'active_vessels' => Vessel::whereHas('vesselStatus', function ($query) {
                $query->where('name', 'Activa');
            })->count(),
            'total_trackings' => Tracking::count(),
            'total_users' => User::count(),
            'vessels_with_alerts' => Vessel::whereHas('vesselStatus', function ($query) {
                $query->where('name', 'Con Alertas');
            })->count(),
            'maintenance_vessels' => Vessel::whereHas('vesselStatus', function ($query) {
                $query->where('name', 'En Mantenimiento');
            })->count(),
        ];
    }

    /**
     * Obtener distribución de embarcaciones por tipo
     */
    public function getVesselsByType(): array
    {
        return DB::table('vessels')
            ->join('vessel_types', 'vessels.vessel_type_id', '=', 'vessel_types.id')
            ->select('vessel_types.name as type', DB::raw('count(*) as count'))
            ->groupBy('vessel_types.id', 'vessel_types.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Obtener distribución de embarcaciones por estado
     */
    public function getVesselsByStatus(): array
    {
        return DB::table('vessels')
            ->join('vessel_statuses', 'vessels.vessel_status_id', '=', 'vessel_statuses.id')
            ->select('vessel_statuses.name as status', DB::raw('count(*) as count'))
            ->groupBy('vessel_statuses.id', 'vessel_statuses.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Obtener posiciones actuales de las embarcaciones
     */
    public function getVesselPositions(): array
    {
        return DB::table('vessels')
            ->join('trackings', function ($join) {
                $join->on('vessels.id', '=', 'trackings.vessel_id')
                     ->whereRaw('trackings.id = (
                         SELECT MAX(id) FROM trackings t2
                         WHERE t2.vessel_id = vessels.id
                     )');
            })
            ->join('vessel_types', 'vessels.vessel_type_id', '=', 'vessel_types.id')
            ->join('vessel_statuses', 'vessels.vessel_status_id', '=', 'vessel_statuses.id')
            ->select(
                'vessels.id',
                'vessels.name',
                'vessels.imo',
                'vessel_types.name as type',
                'vessel_statuses.name as status',
                'trackings.latitude',
                'trackings.longitude',
                'trackings.tracked_at as last_position_at'
            )
            ->orderBy('trackings.tracked_at', 'desc')
            ->limit(100)
            ->get()
            ->toArray();
    }

    /**
     * Obtener actividad mensual de trackings
     */
    public function getMonthlyActivity(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $date->format('F Y'),
                'trackings' => Tracking::whereYear('tracked_at', $date->year)
                    ->whereMonth('tracked_at', $date->month)
                    ->count()
            ];
        }

        return $months;
    }

    /**
     * Obtener métricas de rendimiento de la flota
     */
    public function getPerformanceMetrics(): array
    {
        $metrics = VesselMetric::select(
            DB::raw('AVG(avg_speed) as avg_fleet_speed'),
            DB::raw('AVG(fuel_consumption) as avg_fuel_consumption'),
            DB::raw('SUM(maintenance_count) as total_maintenance'),
            DB::raw('SUM(safety_incidents) as total_incidents')
        )->first();

        return [
            'avg_fleet_speed' => round($metrics->avg_fleet_speed ?? 0, 2),
            'avg_fuel_consumption' => round($metrics->avg_fuel_consumption ?? 0, 2),
            'total_maintenance' => $metrics->total_maintenance ?? 0,
            'total_incidents' => $metrics->total_incidents ?? 0,
        ];
    }

    /**
     * Obtener información sobre la antigüedad de la flota
     */
    public function getFleetAging(): array
    {
        $now = Carbon::now();

        return DB::table('vessels')
            ->select(
                DB::raw('CASE
                    WHEN TIMESTAMPDIFF(YEAR, created_at, NOW()) < 5 THEN "0-5 años"
                    WHEN TIMESTAMPDIFF(YEAR, created_at, NOW()) < 10 THEN "5-10 años"
                    WHEN TIMESTAMPDIFF(YEAR, created_at, NOW()) < 20 THEN "10-20 años"
                    ELSE "20+ años"
                END as age_group'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('age_group')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Obtener todas las métricas del dashboard en una sola llamada
     */
    public function getAllMetrics(): array
    {
        return [
            'main_metrics' => $this->getMainMetrics(),
            'vessels_by_type' => $this->getVesselsByType(),
            'vessels_by_status' => $this->getVesselsByStatus(),
            'monthly_activity' => $this->getMonthlyActivity(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'fleet_aging' => $this->getFleetAging(),
            'last_updated' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Obtener embarcaciones recientes (últimas 10)
     */
    public function getRecentVessels(): array
    {
        return Vessel::with(['vesselType', 'vesselStatus', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($vessel) {
                return [
                    'id' => $vessel->id,
                    'name' => $vessel->name,
                    'imo' => $vessel->imo,
                    'type' => $vessel->vesselType->name,
                    'status' => $vessel->vesselStatus->name,
                    'owner' => $vessel->user->name,
                    'created_at' => $vessel->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    /**
     * Obtener trackings recientes (últimos 20)
     */
    public function getRecentTrackings(): array
    {
        return DB::table('trackings')
            ->join('vessels', 'trackings.vessel_id', '=', 'vessels.id')
            ->join('vessel_types', 'vessels.vessel_type_id', '=', 'vessel_types.id')
            ->select(
                'trackings.id',
                'vessels.name as vessel_name',
                'vessels.imo',
                'vessel_types.name as vessel_type',
                'trackings.latitude',
                'trackings.longitude',
                'trackings.tracked_at'
            )
            ->orderBy('trackings.tracked_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    // ===== MÉTODOS PARA FORMULARIOS =====

    /**
     * Obtener todos los tipos de embarcaciones para formularios
     */
    public function getVesselTypesForForms(): array
    {
        return VesselType::select('id', 'name', 'slug', 'category', 'created_at', 'updated_at')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Obtener todos los estados de embarcaciones para formularios
     */
    public function getVesselStatusForForms(): array
    {
        return VesselStatus::select('id', 'name', 'slug', 'created_at', 'updated_at')
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}
