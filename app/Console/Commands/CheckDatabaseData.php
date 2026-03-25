<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselType;
use App\Models\VesselStatus;
use App\Models\Tracking;
use App\Models\VesselMetric;

class CheckDatabaseData extends Command
{
    protected $signature = 'check:data';
    protected $description = 'Verificar los datos creados en la base de datos';

    public function handle()
    {
        $this->info('🔍 Verificando datos en la base de datos...');
        $this->newLine();

        // Contar registros por tabla
        $this->info('📊 Conteo de registros:');
        $this->table(
            ['Tabla', 'Cantidad'],
            [
                ['users', User::count()],
                ['vessel_types', VesselType::count()],
                ['vessel_statuses', VesselStatus::count()],
                ['vessels', Vessel::count()],
                ['trackings', Tracking::count()],
                ['vessel_metrics', VesselMetric::count()],
            ]
        );

        $this->newLine();

        // Verificar una embarcación con sus relaciones
        $vessel = Vessel::with(['vesselType', 'vesselStatus', 'user'])->first();
        
        if ($vessel) {
            $this->info('🚢 Ejemplo de embarcación con relaciones:');
            $this->line("Embarcación: {$vessel->name}");
            $this->line("IMO: {$vessel->imo}");
            $this->line("Tipo: {$vessel->vesselType->name}");
            $this->line("Estado: {$vessel->vesselStatus->name}");
            $this->line("Usuario: {$vessel->user->name}");
            $this->line("Trackings: " . $vessel->trackings()->count());
            $this->line("Métricas: " . $vessel->metrics()->count());
        } else {
            $this->warn('❌ No se encontraron embarcaciones');
        }

        $this->newLine();

        // Verificar tipos y estados
        $this->info('🏷️  Tipos de embarcaciones:');
        foreach (VesselType::all() as $type) {
            $this->line("- {$type->name} ({$type->vessels()->count()} embarcaciones)");
        }

        $this->newLine();

        $this->info('⚡ Estados de embarcaciones:');
        foreach (VesselStatus::all() as $status) {
            $this->line("- {$status->name} ({$status->vessels()->count()} embarcaciones)");
        }

        $this->newLine();

        // Verificar usuarios por rol
        $this->info('👥 Usuarios por rol:');
        $roles = ['Administrator', 'Manager', 'Operator', 'Viewer', 'Guest'];
        foreach ($roles as $role) {
            $count = User::role($role, 'api')->count();
            $this->line("- {$role}: {$count} usuarios");
        }

        $this->info('✅ Verificación completada');
    }
}
