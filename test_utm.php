<?php
// Lima, Perú: lat=-12.0464, lon=-77.0428 → esperado zona 18L, E≈274000, N≈8668000
$lat = -12.0464;
$lon = -77.0428;

$a   = 6378137.0;
$f   = 1 / 298.257223563;
$b   = $a * (1 - $f);
$e2  = 1 - ($b / $a) ** 2;
$ep2 = $e2 / (1 - $e2);
$k0  = 0.9996;

$zone = (int) floor(($lon + 180) / 6) + 1;
$phi  = deg2rad($lat);
$lam  = deg2rad($lon);
$lam0 = deg2rad(($zone - 1) * 6 - 180 + 3);

$N = $a / sqrt(1 - $e2 * sin($phi) ** 2);
$T = tan($phi) ** 2;
$C = $ep2 * cos($phi) ** 2;
$A = cos($phi) * ($lam - $lam0);

$M = $a * (
      (1 - $e2 / 4 - 3 * $e2 ** 2 / 64  - 5  * $e2 ** 3 / 256)   * $phi
    - (3 * $e2 / 8 + 3 * $e2 ** 2 / 32  + 45 * $e2 ** 3 / 1024)  * sin(2 * $phi)
    + (15 * $e2 ** 2 / 256 + 45 * $e2 ** 3 / 1024)                * sin(4 * $phi)
    - (35 * $e2 ** 3 / 3072)                                       * sin(6 * $phi)
);

$easting = $k0 * $N * (
    $A
    + (1 - $T + $C) * $A ** 3 / 6
    + (5 - 18 * $T + $T ** 2 + 72 * $C - 58 * $ep2) * $A ** 5 / 120
) + 500000;

$northing = $k0 * (
    $M + $N * tan($phi) * (
        $A ** 2 / 2
        + (5  - $T  + 9  * $C + 4  * $C ** 2) * $A ** 4 / 24
        + (61 - 58 * $T + $T ** 2 + 600 * $C - 330 * $ep2) * $A ** 6 / 720
    )
);
if ($lat < 0) $northing += 10_000_000;

$letters     = 'CDEFGHJKLMNPQRSTUVWX';
$letterIndex = max(0, min(19, (int) floor(($lat + 80) / 8)));
$letter      = $letters[$letterIndex];

echo "=== UTM Lima, Peru ===\n";
echo "Zona:     {$zone}{$letter}   (esperado: 18L)\n";
echo "Easting:  " . round($easting)  . " m   (esperado: ~274122)\n";
echo "Northing: " . round($northing) . " m   (esperado: ~8668667)\n\n";

// QuadKey Lima zoom 15
$zoom   = 15;
$lat2   = max(-85.05112878, min(85.05112878, $lat));
$tiles  = 1 << $zoom;
$x      = (int) floor(($lon + 180) / 360 * $tiles);
$sinLat = sin(deg2rad($lat2));
$y      = (int) floor((0.5 - log((1 + $sinLat) / (1 - $sinLat)) / (4 * M_PI)) * $tiles);
$tx     = max(0, min($tiles - 1, $x));
$ty     = max(0, min($tiles - 1, $y));

$key = '';
for ($i = $zoom; $i > 0; $i--) {
    $digit = 0;
    $mask  = 1 << ($i - 1);
    if (($tx & $mask) !== 0) $digit += 1;
    if (($ty & $mask) !== 0) $digit += 2;
    $key .= $digit;
}
echo "=== QuadKey Lima, zoom 15 ===\n";
echo "Tile X:  {$tx}\n";
echo "Tile Y:  {$ty}\n";
echo "QuadKey: {$key}\n";
