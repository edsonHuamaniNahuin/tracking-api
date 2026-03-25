<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class TestApiEndpoints extends Command
{
    protected $signature = 'test:api';
    protected $description = 'Probar los endpoints de la API';

    protected $baseUrl = 'http://127.0.0.1:8000/api/v1';
    protected $token;

    public function handle()
    {
        $this->info('🧪 Probando endpoints de la API...');
        $this->newLine();

        // Autenticarse
        if (!$this->authenticate()) {
            $this->error('❌ Fallo la autenticación');
            return;
        }

        // Probar endpoints del dashboard
        $this->testDashboardEndpoints();

        // Probar endpoints de vessels
        $this->testVesselEndpoints();

        // Probar endpoints de trackings
        $this->testTrackingEndpoints();

        $this->info('✅ Pruebas completadas');
    }

    protected function authenticate()
    {
        $this->info('🔐 Autenticando...');

        $response = Http::post($this->baseUrl . '/auth/login', [
            'email' => 'test@example.com',
            'password' => 'secret123'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->token = $data['token'];
            $this->line("✅ Autenticado como: {$data['user']['name']}");
            return true;
        }

        $this->error('❌ Error de autenticación: ' . $response->body());
        return false;
    }

    protected function testDashboardEndpoints()
    {
        $this->info('📊 Probando endpoints del dashboard...');

        $endpoints = [
            '/dashboard/metrics' => 'Métricas principales',
            '/dashboard/vessels-by-type' => 'Embarcaciones por tipo',
            '/dashboard/vessels-by-status' => 'Embarcaciones por estado',
            '/dashboard/vessel-positions' => 'Posiciones de embarcaciones',
            '/dashboard/all-metrics' => 'Todas las métricas'
        ];

        foreach ($endpoints as $endpoint => $description) {
            $response = Http::withToken($this->token)->get($this->baseUrl . $endpoint);

            if ($response->successful()) {
                $data = $response->json();
                $this->line("✅ {$description}: " . count($data) . " elementos");
            } else {
                $this->warn("⚠️  {$description}: Error {$response->status()}");
            }
        }
    }

    protected function testVesselEndpoints()
    {
        $this->info('🚢 Probando endpoints de embarcaciones...');

        // Listar vessels
        $response = Http::withToken($this->token)->get($this->baseUrl . '/vessels');

        if ($response->successful()) {
            $data = $response->json();
            $this->line("✅ Lista de embarcaciones: " . count($data) . " elementos");

            // Probar detalle de una vessel
            if (count($data) > 0) {
                $vesselId = $data[0]['id'];
                $detailResponse = Http::withToken($this->token)->get($this->baseUrl . "/vessels/{$vesselId}");

                if ($detailResponse->successful()) {
                    $vessel = $detailResponse->json();
                    $this->line("✅ Detalle de embarcación: {$vessel['name']}");
                } else {
                    $this->warn("⚠️  Error al obtener detalle de embarcación");
                }
            }
        } else {
            $this->warn("⚠️  Error al listar embarcaciones: " . $response->status());
        }
    }

    protected function testTrackingEndpoints()
    {
        $this->info('📍 Probando endpoints de tracking...');

        $response = Http::withToken($this->token)->get($this->baseUrl . '/trackings');

        if ($response->successful()) {
            $data = $response->json();
            $this->line("✅ Lista de trackings: " . count($data) . " elementos");
        } else {
            $this->warn("⚠️  Error al listar trackings: " . $response->status());
        }
    }
}
