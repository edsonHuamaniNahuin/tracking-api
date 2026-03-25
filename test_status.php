<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$token = auth()->login(\App\Models\User::first());

// Test 1: Device status endpoint
$request = \Illuminate\Http\Request::create('/api/v1/vessels/13/device/status', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);
echo "=== DEVICE STATUS ===\n";
echo "Code: " . $response->getStatusCode() . "\n";
$statusData = json_decode($response->getContent(), true);
echo "last_position: " . json_encode($statusData['data']['last_position'] ?? null, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Trackings list endpoint (same as frontend map uses)
$request2 = \Illuminate\Http\Request::create('/api/v1/trackings?vessel_id=13&date_from=2026-03-23&date_to=2026-03-24&per_page=100', 'GET');
$request2->headers->set('Authorization', 'Bearer ' . $token);
$response2 = $kernel->handle($request2);
echo "=== TRACKINGS LIST ===\n";
echo "Code: " . $response2->getStatusCode() . "\n";
$trackData = json_decode($response2->getContent(), true);
echo "Total items: " . count($trackData['data'] ?? []) . "\n";
if (!empty($trackData['data'])) {
    echo "First: " . json_encode($trackData['data'][0]) . "\n";
    echo "Last:  " . json_encode(end($trackData['data'])) . "\n";
}
