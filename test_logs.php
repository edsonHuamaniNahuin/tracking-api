<?php
$ch = curl_init('http://localhost:8000/api/v1/vessels/13/device/logs?limit=3');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP $httpCode\n";
$data = json_decode($response, true);
echo "success: " . ($data['success'] ?? 'null') . "\n";
echo "logs count: " . count($data['data']['logs'] ?? []) . "\n";
echo "telemetry_count: " . ($data['data']['device']['telemetry_count'] ?? 'null') . "\n";
echo "tracking_count: " . ($data['data']['device']['tracking_count'] ?? 'null') . "\n";
if (!empty($data['data']['logs'])) {
    $l = $data['data']['logs'][0];
    echo "First log type: " . $l['type'] . "\n";
    echo "First log gps_fix: " . ($l['gps_fix'] ? 'true' : 'false') . "\n";
    echo "First log lat: " . $l['latitude'] . "\n";
    echo "First log lon: " . $l['longitude'] . "\n";
}
