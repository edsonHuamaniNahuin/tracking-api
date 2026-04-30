<?php

namespace App\Services;

use App\Events\TrackingCreated;
use App\Models\Tracking;
use App\Models\Vessel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TrackingService
{
    /**
     * Obtener trackings con filtros y paginación
     */
    public function getTrackings(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($user);

        // Aplicar filtros
        $this->applyFilters($query, $filters);

        // Ordenar por fecha más reciente
        $query->orderBy('tracked_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Obtener trackings de una embarcación específica
     */
    public function getTrackingsByVessel(Vessel $vessel, int $perPage = 15): LengthAwarePaginator
    {
        return $vessel->trackings()
            ->orderBy('tracked_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Obtener trackings recientes
     */
    public function getRecentTrackings(User $user, int $limit = 20): Collection
    {
        $query = $this->buildBaseQuery($user);

        return $query->orderBy('tracked_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Crear un nuevo tracking
     */
    public function createTracking(array $data): Tracking
    {
        $tracking = Tracking::create($data);
        $tracking->load('vessel.vesselType', 'vessel.vesselStatus', 'vessel.user');

        // Notificar en tiempo real al frontend vía Reverb (WebSocket).
        // El evento se envía en el canal private-vessel.{vessel_id}.tracking.
        broadcast(new TrackingCreated($tracking));

        return $tracking;
    }

    /**
     * Actualizar un tracking existente
     */
    public function updateTracking(Tracking $tracking, array $data): Tracking
    {
        $tracking->update($data);
        $tracking->load('vessel.vesselType', 'vessel.vesselStatus', 'vessel.user');

        return $tracking;
    }

    /**
     * Eliminar un tracking
     */
    public function deleteTracking(Tracking $tracking): bool
    {
        return $tracking->delete();
    }

    /**
     * Obtener estadísticas de trackings
     */
    public function getTrackingStats(User $user): array
    {
        $query = $this->buildBaseQuery($user);

        $total = $query->count();
        $today = $query->whereDate('tracked_at', Carbon::today())->count();
        $thisWeek = $query->whereBetween('tracked_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ])->count();
        $thisMonth = $query->whereMonth('tracked_at', Carbon::now()->month)
            ->whereYear('tracked_at', Carbon::now()->year)
            ->count();

        return [
            'total' => $total,
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
        ];
    }

    /**
     * Construir query base según permisos del usuario
     */
    private function buildBaseQuery(User $user): Builder
    {
        $query = Tracking::with(['vessel.vesselType', 'vessel.vesselStatus', 'vessel.user']);

        // Si no es Administrator, solo puede ver trackings de sus embarcaciones
        if (!$user->hasRole('Administrator', 'api')) {
            $query->whereHas('vessel', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query;
    }

    /**
     * Aplicar filtros a la query.
     *
     * Política de granularidad:
     * - HOY       → se respetan las horas enviadas (filtro preciso por hora).
     * - AYER ←   → se normaliza al día completo (startOfDay / endOfDay),
     *               independientemente de la hora recibida.
     *
     * Esto permite cachear resultados históricos por día completo tanto en el
     * servidor como en el cliente, ya que el pasado es inmutable.
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['vessel_id'])) {
            $query->where('vessel_id', $filters['vessel_id']);
        }

        $todayStart = Carbon::today(); // midnight de hoy en el timezone del sistema

        if (!empty($filters['date_from'])) {
            $from = Carbon::parse($filters['date_from']);
            // Si es un día anterior a hoy → forzar inicio del día completo
            if ($from->lt($todayStart)) {
                $from->startOfDay();
            }
            $query->where('tracked_at', '>=', $from);
        }

        if (!empty($filters['date_to'])) {
            $to = Carbon::parse($filters['date_to']);
            // Si es un día anterior a hoy → forzar fin del día completo
            if ($to->lt($todayStart)) {
                $to->endOfDay();
            }
            $query->where('tracked_at', '<=', $to);
        }

        if (!empty($filters['days_ago'])) {
            $query->where('tracked_at', '>=', Carbon::now()->subDays($filters['days_ago']));
        }
    }

    /**
     * Obtener los días únicos en que una embarcación tiene registros de tracking.
     *
     * Query ligera: un solo GROUP BY sobre (vessel_id, DATE(tracked_at)).
     * El índice compuesto (vessel_id, tracked_at) cubre la operación.
     * Retorna array de strings 'YYYY-MM-DD' ordenados descendente.
     */
    public function getTrackingDays(Vessel $vessel): array
    {
        return $vessel->trackings()
            ->selectRaw('DATE(tracked_at) as day')
            ->groupBy('day')
            ->orderBy('day', 'desc')
            ->pluck('day')
            ->toArray();
    }

    /**
     * Validar que el usuario puede acceder a una embarcación
     */
    public function canAccessVessel(User $user, int $vesselId): bool
    {
        if ($user->hasRole('Administrator', 'api')) {
            return true;
        }

        return Vessel::where('id', $vesselId)
            ->where('user_id', $user->id)
            ->exists();
    }
}
