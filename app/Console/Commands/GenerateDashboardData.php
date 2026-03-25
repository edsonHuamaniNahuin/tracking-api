<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GenerateDashboardData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:generate-data {--fresh : Ejecutar migrate:fresh antes de sembrar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera datos de prueba para el dashboard de tracking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚢 Generando datos para el dashboard de tracking...');

        if ($this->option('fresh')) {
            $this->warn('⚠️  Ejecutando migrate:fresh - Se perderán todos los datos existentes');
            if ($this->confirm('¿Estás seguro de que quieres continuar?')) {
                $this->info('🔄 Ejecutando migrate:fresh...');
                Artisan::call('migrate:fresh');
                $this->info('✅ Base de datos reiniciada');
            } else {
                $this->error('❌ Operación cancelada');
                return;
            }
        }

        $this->info('🌱 Ejecutando seeders...');

        // Ejecutar solo el seeder del dashboard
        Artisan::call('db:seed', ['--class' => 'DashboardMetricsSeeder']);

        $this->info('✅ Datos del dashboard generados exitosamente');

        $this->newLine();
        $this->info('📊 Datos generados:');
        $this->line('• ~245 embarcaciones distribuidas por tipo');
        $this->line('• Tipos: Carguero, Petrolero, Pasajeros, Pesquero, Remolcador, Otros');
        $this->line('• Estados: Activa, En Mantenimiento, Inactiva, Con Alertas');
        $this->line('• Trackings históricos para embarcaciones activas');
        $this->line('• Métricas de rendimiento por los últimos 12 meses');

        $this->newLine();
        $this->info('🔗 Endpoints disponibles:');
        $this->line('• GET /api/v1/dashboard/all-metrics - Todas las métricas');
        $this->line('• GET /api/v1/dashboard/metrics - Métricas principales');
        $this->line('• GET /api/v1/dashboard/vessels-by-type - Embarcaciones por tipo');
        $this->line('• GET /api/v1/dashboard/monthly-activity - Actividad mensual');
        $this->line('• GET /api/v1/dashboard/vessels-by-status - Embarcaciones por estado');
        $this->line('• GET /api/v1/dashboard/fleet-aging - Antigüedad de la flota');
        $this->line('• GET /api/v1/dashboard/performance-metrics - Métricas de rendimiento');
        $this->line('• GET /api/v1/dashboard/vessel-positions - Posiciones para mapa');

        $this->newLine();
        $this->info('💡 Usa --fresh para reiniciar completamente la base de datos');
        $this->info('🎯 Usuario de prueba: admin@tracking.com / admin123');
    }
}
