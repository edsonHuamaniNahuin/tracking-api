<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$v = \App\Models\Vessel::find(13);

// Simulate a POST /api/v1/device/ping with GPS coordinates
$request = \Illuminate\Http\Request::create('/api/v1/device/ping', 'POST', [
    'lat'         => -11.932867,
    'lon'         => -76.977494,
    'speed'       => 0.86,
    'course'      => 64.58,
    'altitude'    => 597.10,
    'satellites'  => 7,
    'hdop'        => 2.54,
    'firmware'    => '1.0.0',
    'device_type' => 'ESP32-GPS-TRACKER',
    'uptime'      => 100,
]);

$request->headers->set('X-Device-Token', $v->device_token);
$request->attributes->set('_vessel', $v);

$ctrl = new \App\Http\Controllers\Api\DeviceController();
$resp = $ctrl->poll($request);

echo "HTTP Code: " . $resp->getStatusCode() . "\n";
echo "Response: " . $resp->getContent() . "\n\n";

// Verify tracking was saved
$count = \Illuminate\Support\Facades\DB::table('trackings')
    ->where('vessel_id', 13)
    ->whereNull('deleted_at')
    ->count();

echo "Trackings for vessel 13: $count\n";

$last = \Illuminate\Support\Facades\DB::table('trackings')
    ->where('vessel_id', 13)
    ->whereNull('deleted_at')
    ->orderByDesc('id')
    ->first();

echo "Last tracking: " . json_encode($last) . "\n";
