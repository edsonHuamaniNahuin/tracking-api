<?php

namespace App\Services;

use App\DTO\PaginatedResponse;
use App\DTO\VesselResponse;
use App\Http\Requests\StoreVesselRequest;
use App\Repositories\VesselRepository;
use App\Models\Vessel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;


class VesselService
{
    public function __construct(protected VesselRepository $repo) {}


    public function paginateForUser(
        int     $page,
        int     $perPage,
        ?string $filterName = null,
        ?string $filterImo  = null,
        ?int    $typeId     = null,
        ?int    $statusId   = null,
        bool    $ownOnly    = false
    ): PaginatedResponse {
        /** @var \App\Models\User $user */
        $user    = Auth::user();
        $isAdmin = !$ownOnly && $user->hasRole('Administrator', 'api');

        Gate::authorize('viewAny', Vessel::class);

        // 1) Traemos el paginator con eager-loading
        $paginator = $this->repo->paginateByUser(
            $isAdmin,
            $user->id,
            $perPage,
            $page,
            $filterName,
            $filterImo,
            $typeId,
            $statusId
        );

        // 2) Convertimos la colección interna a array. Allí cada Vessel ya incluye
        //    “vessel_type” y “vessel_status” y NO incluye los campos ocultos ($hidden).
        $rows = $paginator->getCollection()->toArray();

        // 3) Metadatos de paginación
        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem() ?: 0,
            'to'           => $paginator->lastItem() ?: 0,
        ];

        return new PaginatedResponse($rows, $meta, 200, 'Listado paginado de embarcaciones');
    }



    /**
     * Lista todas las embarcaciones del usuario autenticado.
     *
     * @return Vessel[]
     */
    public function listAllForUser(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $this->repo->findAllByUser($user->id)->all();
    }

    /**
     * Crea una nueva embarcación para el usuario.
     *
     * @param  array{name:string,imo:?string}  $data
     * @return VesselResponse
     */
    public function create(array $data): VesselResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        Gate::authorize('create', Vessel::class);

        $payload = [
            'name'    => $data['name'],
            'imo'     => $data['imo'] ?? null,
            'user_id' => $user->id,
            'vessel_type_id'   => $data['vessel_type_id'] ?? null,
            'vessel_status_id' => $data['vessel_status_id'] ?? null,
        ];

        $vessel =  $this->repo->create($payload);
        return new VesselResponse($vessel, 201, 'Embarcación creada');
    }

    /**
     * Recupera un Vessel por su ID y verifica permiso “view”.
     *
     * @param  int  $id
     * @return VesselResponse
     *
     * @throws AuthorizationException|\Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function show(int $id): VesselResponse
    {
        $vessel = $this->repo->findById($id);
        abort_if(!$vessel, 404, 'Embarcación no encontrada');

        Gate::authorize('view', $vessel);

        return new VesselResponse($vessel, 200, 'Detalle de embarcación');
    }

    /**
     * Actualiza un Vessel existente.
     *
     * @param  int   $id
     * @param  array{name?:string,imo?:?string}  $data
     * @return VesselResponse
     *
     * @throws AuthorizationException|\Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, array $data): VesselResponse
    {
        $vessel = $this->repo->findById($id);
        abort_if(!$vessel, 404, 'Embarcación no encontrada');

        Gate::authorize('update', $vessel);

        $updated = $this->repo->update($vessel, $data);
        return new VesselResponse($updated, 200, 'Embarcación actualizada');
    }


    /**
     * Elimina un Vessel.
     *
     * @param  int  $id
     * @return VesselResponse
     *
     * @throws AuthorizationException|\Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): VesselResponse
    {
        $vessel = $this->repo->findById($id);
        abort_if(!$vessel, 404, 'Embarcación no encontrada');

        Gate::authorize('delete', $vessel);

        $this->repo->delete($vessel);
        return new VesselResponse(null, 200, 'Embarcación eliminada');
    }



    public function getFleetAging(): array
    {
        // Obtenemos todos los vessels (o los filtrados)
        $vessels = Vessel::withTrashed()->get();

        return $vessels->map(function (Vessel $v) {
            $ageInYears = Carbon::parse($v->created_at)->diffInYears(now());
            return [
                'id'       => $v->id,
                'name'     => $v->name,
                'age_years' => $ageInYears,
                'type'     => $v->type?->name,
                'status'   => $v->status?->name,
            ];
        })->toArray();
    }


    public function getActivityByType()
    {
        $results = DB::table('trackings')
            ->join('vessels', 'trackings.vessel_id', '=', 'vessels.id')
            ->join('vessel_types', 'vessels.vessel_type_id', '=', 'vessel_types.id')
            ->select('vessel_types.id as type_id', 'vessel_types.name as type_name', DB::raw('COUNT(trackings.id) as total_trackings'))
            ->groupBy('vessel_types.id', 'vessel_types.name')
            ->get();

        return $results;
    }

    /**
     * Obtiene las métricas principales del dashboard
     */
    public function getDashboardMetrics(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user->hasRole('Administrator', 'api');

        $query = $isAdmin ? Vessel::query() : Vessel::where('user_id', $user->id);

        $totalVessels = $query->count();
        $activeVessels = $query->whereHas('vesselStatus', function($q) {
            $q->where('name', 'Activa');
        })->count();

        $maintenanceVessels = $query->whereHas('vesselStatus', function($q) {
            $q->where('name', 'En Mantenimiento');
        })->count();

        $alertVessels = $query->whereHas('vesselStatus', function($q) {
            $q->where('name', 'Con Alertas');
        })->count();

        return [
            'total_vessels' => $totalVessels,
            'active_vessels' => $activeVessels,
            'maintenance_vessels' => $maintenanceVessels,
            'alert_vessels' => $alertVessels,
        ];
    }

    /**
     * Obtiene embarcaciones por tipo para gráfico de barras
     */
    public function getVesselsByType(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user->hasRole('Administrator', 'api');

        $query = $isAdmin ? Vessel::query() : Vessel::where('user_id', $user->id);

        $results = $query->with('vesselType')
            ->get()
            ->groupBy('vesselType.name')
            ->map(function ($vessels, $typeName) {
                return [
                    'name' => $typeName,
                    'value' => $vessels->count(),
                    'color' => $this->getTypeColor($typeName),
                ];
            })
            ->values()
            ->toArray();

        return $results;
    }

    /**
     * Obtiene actividad mensual por tipo de embarcación
     */
    public function getMonthlyActivityByType(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user->hasRole('Administrator', 'api');

        // Obtener datos de trackings por mes y tipo
        $query = DB::table('trackings')
            ->join('vessels', 'trackings.vessel_id', '=', 'vessels.id')
            ->join('vessel_types', 'vessels.vessel_type_id', '=', 'vessel_types.id')
            ->whereYear('trackings.tracked_at', Carbon::now()->year);

        if (!$isAdmin) {
            $query->where('vessels.user_id', $user->id);
        }

        $results = $query->select(
                DB::raw('MONTH(trackings.tracked_at) as month'),
                'vessel_types.name as type_name',
                DB::raw('COUNT(trackings.id) as activity_count')
            )
            ->groupBy(DB::raw('MONTH(trackings.tracked_at)'), 'vessel_types.name')
            ->get();

        // Organizar datos por mes
        $monthlyData = [];
        $months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        for ($i = 1; $i <= 12; $i++) {
            $monthData = ['name' => $months[$i - 1]];

            // Agregar datos por tipo
            foreach (['Carguero', 'Petrolero', 'Pasajeros'] as $type) {
                $typeResult = $results->where('month', $i)->where('type_name', $type)->first();
                $monthData[strtolower($type)] = $typeResult ? $typeResult->activity_count : 0;
            }

            $monthlyData[] = $monthData;
        }

        return $monthlyData;
    }

    /**
     * Obtiene embarcaciones por estado para gráfico de estado
     */
    public function getVesselsByStatus(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user->hasRole('Administrator', 'api');

        $query = $isAdmin ? Vessel::query() : Vessel::where('user_id', $user->id);

        $results = $query->with('vesselStatus')
            ->get()
            ->groupBy('vesselStatus.name')
            ->map(function ($vessels, $statusName) {
                return [
                    'name' => $statusName,
                    'value' => $vessels->count(),
                    'color' => $this->getStatusColor($statusName),
                    'icon' => $this->getStatusIcon($statusName),
                ];
            })
            ->values()
            ->toArray();

        return $results;
    }

    /**
     * Obtiene distribución de embarcaciones por antigüedad
     */
    public function getFleetAgingDistribution(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user->hasRole('Administrator', 'api');

        $query = $isAdmin ? Vessel::query() : Vessel::where('user_id', $user->id);
        $vessels = $query->get();

        $ageRanges = [
            '0-5 años' => 0,
            '6-10 años' => 0,
            '11-15 años' => 0,
            '16-20 años' => 0,
            '21-25 años' => 0,
            '>25 años' => 0,
        ];

        foreach ($vessels as $vessel) {
            $age = Carbon::parse($vessel->created_at)->diffInYears(now());

            if ($age <= 5) {
                $ageRanges['0-5 años']++;
            } elseif ($age <= 10) {
                $ageRanges['6-10 años']++;
            } elseif ($age <= 15) {
                $ageRanges['11-15 años']++;
            } elseif ($age <= 20) {
                $ageRanges['16-20 años']++;
            } elseif ($age <= 25) {
                $ageRanges['21-25 años']++;
            } else {
                $ageRanges['>25 años']++;
            }
        }

        $colors = ['#22c55e', '#10b981', '#0ea5e9', '#6366f1', '#8b5cf6', '#a855f7'];
        $result = [];
        $colorIndex = 0;

        foreach ($ageRanges as $range => $count) {
            $result[] = [
                'name' => $range,
                'value' => $count,
                'color' => $colors[$colorIndex++],
            ];
        }

        return $result;
    }

    /**
     * Obtiene métricas de rendimiento por tipo de embarcación
     */
    public function getPerformanceMetrics(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user->hasRole('Administrator', 'api');

        // Obtener métricas promedio por tipo
        $query = DB::table('vessel_metrics')
            ->join('vessels', 'vessel_metrics.vessel_id', '=', 'vessels.id')
            ->join('vessel_types', 'vessels.vessel_type_id', '=', 'vessel_types.id');

        if (!$isAdmin) {
            $query->where('vessels.user_id', $user->id);
        }

        $metrics = $query->select(
                'vessel_types.name as type_name',
                DB::raw('AVG(vessel_metrics.avg_speed) as avg_speed'),
                DB::raw('AVG(vessel_metrics.fuel_consumption) as avg_fuel'),
                DB::raw('AVG(vessel_metrics.maintenance_count) as avg_maintenance'),
                DB::raw('AVG(vessel_metrics.safety_incidents) as avg_incidents')
            )
            ->groupBy('vessel_types.name')
            ->get()
            ->keyBy('type_name');

        // Simular datos de rendimiento para el radar chart
        $performanceData = [
            ['subject' => 'Eficiencia', 'carguero' => 80, 'petrolero' => 90, 'pasajeros' => 70],
            ['subject' => 'Velocidad', 'carguero' => 65, 'petrolero' => 60, 'pasajeros' => 85],
            ['subject' => 'Capacidad', 'carguero' => 90, 'petrolero' => 85, 'pasajeros' => 75],
            ['subject' => 'Consumo', 'carguero' => 75, 'petrolero' => 65, 'pasajeros' => 80],
            ['subject' => 'Mantenimiento', 'carguero' => 70, 'petrolero' => 75, 'pasajeros' => 85],
            ['subject' => 'Seguridad', 'carguero' => 85, 'petrolero' => 80, 'pasajeros' => 90],
        ];

        return $performanceData;
    }

    /**
     * Obtiene posiciones de embarcaciones para el mapa
     */
    public function getVesselPositions(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user->hasRole('Administrator', 'api');

        $query = $isAdmin ? Vessel::query() : Vessel::where('user_id', $user->id);

        $vessels = $query->with(['vesselType', 'trackings' => function($q) {
                $q->latest()->limit(1);
            }])
            ->whereHas('vesselStatus', function($q) {
                $q->where('name', 'Activa');
            })
            ->get();

        $positions = [];
        foreach ($vessels as $vessel) {
            $latestTracking = $vessel->trackings->first();
            if ($latestTracking) {
                $positions[] = [
                    'id' => $vessel->id,
                    'name' => $vessel->name,
                    'position' => [
                        'x' => $this->normalizeCoordinate($latestTracking->longitude, -180, 180, 0, 100),
                        'y' => $this->normalizeCoordinate($latestTracking->latitude, -90, 90, 0, 100),
                    ],
                    'type' => $vessel->vesselType->name,
                    'coordinates' => [
                        'lat' => $latestTracking->latitude,
                        'lng' => $latestTracking->longitude,
                    ],
                ];
            }
        }

        return $positions;
    }

    /**
     * Métodos auxiliares para colores e iconos
     */
    private function getTypeColor($typeName): string
    {
        $colors = [
            'Carguero' => '#2563eb',
            'Petrolero' => '#0891b2',
            'Pasajeros' => '#4f46e5',
            'Pesquero' => '#0d9488',
            'Remolcador' => '#7c3aed',
            'Otros' => '#6366f1',
        ];

        return $colors[$typeName] ?? '#6b7280';
    }

    private function getStatusColor($statusName): string
    {
        $colors = [
            'Activa' => '#22c55e',
            'En Mantenimiento' => '#f59e0b',
            'Inactiva' => '#64748b',
            'Con Alertas' => '#ef4444',
        ];

        return $colors[$statusName] ?? '#6b7280';
    }

    private function getStatusIcon($statusName): string
    {
        $icons = [
            'Activa' => 'CheckCircle',
            'En Mantenimiento' => 'Anchor',
            'Inactiva' => 'Ship',
            'Con Alertas' => 'AlertTriangle',
        ];

        return $icons[$statusName] ?? 'Ship';
    }

    private function normalizeCoordinate($value, $minInput, $maxInput, $minOutput, $maxOutput): float
    {
        return $minOutput + (($value - $minInput) / ($maxInput - $minInput)) * ($maxOutput - $minOutput);
    }
}
