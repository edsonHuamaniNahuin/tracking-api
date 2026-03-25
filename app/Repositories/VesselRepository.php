<?php

namespace App\Repositories;

use App\Models\Vessel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VesselRepository
{
    /**
     * Paginación de embarcaciones, con filtros opcionales.
     *
     * @param  bool         $isAdmin
     * @param  int          $userId
     * @param  int          $perPage
     * @param  int          $page
     * @param  string|null  $filterName
     * @param  string|null  $filterImo
     * @param  int|null     $typeId
     * @param  int|null     $statusId
     * @return LengthAwarePaginator<Vessel>
     */
    public function paginateByUser(
        bool $isAdmin,
        int $userId,
        int $perPage,
        int $page,
        ?string $filterName = null,
        ?string $filterImo  = null,
        ?int $typeId = null,
        ?int $statusId = null
    ): LengthAwarePaginator {
        $query = Vessel::with(['vesselType', 'vesselStatus']);

        if (! $isAdmin) {
            $query->where('user_id', $userId);
        }

        if (! empty(trim((string) $filterName))) {
            $query->where('name', 'like', '%' . trim($filterName) . '%');
        }

        if (! empty(trim((string) $filterImo))) {
            $query->where('imo', 'like', '%' . trim($filterImo) . '%');
        }

        if ($typeId !== null) {
            $query->where('vessel_type_id', $typeId);
        }

        if ($statusId !== null) {
            $query->where('vessel_status_id', $statusId);
        }

        return $query
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }


    /**
     * Obtiene todas las embarcaciones de un usuario.
     *
     * @param  int  $userId
     * @return Collection<int, Vessel>
     */
    public function findAllByUser(int $userId): Collection
    {
        return Vessel::where('user_id', $userId)->get();
    }

    /**
     * Obtiene todas las embarcaciones sin filtrar.
     *
     * @return Collection<int, Vessel>
     */
    public function findAll(): Collection
    {
        return Vessel::all();
    }

    /**
     * Obtiene un Vessel por su ID.
     */
    public function findById(int $id): ?Vessel
    {
        return Vessel::find($id);
    }

    /**
     * Crea un nuevo Vessel.
     *
     * @param  array{name:string,imo:?string,user_id:int}  $data
     */
    public function create(array $data): Vessel
    {
        return Vessel::create($data);
    }

    /**
     * Actualiza un Vessel existente.
     */
    public function update(Vessel $vessel, array $data): Vessel
    {
        $vessel->fill($data);
        $vessel->save();
        return $vessel;
    }

    /**
     * Elimina un Vessel.
     */
    public function delete(Vessel $vessel): void
    {
        $vessel->delete();
    }
}
