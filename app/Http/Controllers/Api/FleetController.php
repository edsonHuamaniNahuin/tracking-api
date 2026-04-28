<?php

namespace App\Http\Controllers\Api;

use App\DTO\FleetResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFleetRequest;
use App\Http\Requests\UpdateFleetRequest;
use App\Models\Fleet;
use App\Models\Vessel;
use App\Services\FleetService;

class FleetController extends Controller
{
    public function __construct(private FleetService $service)
    {
        $this->middleware('auth:api');
    }

    /**
     * Lista flotas del usuario (o todas si es admin).
     */
    public function index(): FleetResponse
    {
        return new FleetResponse(
            $this->service->list(),
            200,
            'Listado de flotas'
        );
    }

    /**
     * Detalle de una flota con sus embarcaciones.
     */
    public function show(Fleet $fleet): FleetResponse
    {
        return new FleetResponse(
            $this->service->findOrFail($fleet->id),
            200,
            'Detalle de flota'
        );
    }

    /**
     * Crea una nueva flota.
     */
    public function store(StoreFleetRequest $request): FleetResponse
    {
        return new FleetResponse(
            $this->service->create($request->validated()),
            201,
            'Flota creada'
        );
    }

    /**
     * Actualiza nombre, descripción o color de una flota.
     */
    public function update(UpdateFleetRequest $request, Fleet $fleet): FleetResponse
    {
        return new FleetResponse(
            $this->service->update($fleet, $request->validated()),
            200,
            'Flota actualizada'
        );
    }

    /**
     * Elimina (soft delete) una flota.
     * Los vessels de la flota quedan con fleet_id = NULL.
     */
    public function destroy(Fleet $fleet): FleetResponse
    {
        $this->service->delete($fleet);

        return new FleetResponse(null, 200, 'Flota eliminada');
    }

    /**
     * Asigna una embarcación a esta flota.
     * POST /api/v1/fleets/{fleet}/vessels/{vessel}
     */
    public function assignVessel(Fleet $fleet, Vessel $vessel): FleetResponse
    {
        $this->service->assignVessel($fleet, $vessel);

        return new FleetResponse(null, 200, 'Embarcación asignada a la flota');
    }

    /**
     * Quita una embarcación de esta flota (fleet_id → NULL).
     * DELETE /api/v1/fleets/{fleet}/vessels/{vessel}
     */
    public function removeVessel(Fleet $fleet, Vessel $vessel): FleetResponse
    {
        $this->service->removeVessel($fleet, $vessel);

        return new FleetResponse(null, 200, 'Embarcación removida de la flota');
    }
}
