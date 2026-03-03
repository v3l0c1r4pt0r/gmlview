<?php
header('Content-Type: application/json; charset=utf-8');

if (php_sapi_name() === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

$file = $_GET['file'] ?? null;
$filter = $_GET['idDzialki'] ?? null;

if (!$file || !file_exists($file)) {
    echo json_encode(["error" => "Brak pliku"]);
    exit;
}

/* -----------------------
   EPSG:2178 → WGS84
----------------------- */
function epsg2178_to_wgs84($x, $y)
{
    $a = 6378137.0;
    $e2 = 0.00669437999013;
    $k0 = 0.9993;
    $lon0 = deg2rad(19);

    $x -= 500000;
    $y /= $k0;

    $e = sqrt($e2);
    $mu = $y / ($a * (1 - $e2/4 - 3*$e2*$e2/64 - 5*$e2*$e2*$e2/256));

    $phi1 = $mu
        + (3*$e/2 - 27*pow($e,3)/32)*sin(2*$mu)
        + (21*$e*$e/16 - 55*pow($e,4)/32)*sin(4*$mu);

    $C1 = $e2 * pow(cos($phi1),2)/(1-$e2);
    $T1 = pow(tan($phi1),2);
    $N1 = $a / sqrt(1-$e2*pow(sin($phi1),2));
    $R1 = $a*(1-$e2)/pow(1-$e2*pow(sin($phi1),2),1.5);
    $D = $x/($N1*$k0);

    $lat = $phi1 - ($N1*tan($phi1)/$R1)*($D*$D/2);
    $lon = $lon0 + ($D - (1+2*$T1+$C1)*pow($D,3)/6)/cos($phi1);

    return [rad2deg($lon), rad2deg($lat)]; // GeoJSON = [lon, lat]
}

/* -----------------------
   XML
----------------------- */
$xml = simplexml_load_file($file);
$xml->registerXPathNamespace('gml', 'http://www.opengis.net/gml/3.2');
$xml->registerXPathNamespace('rcw', 'urn:gugik:specyfikacje:gmlas:rejestrcennieruchomosci:1.0');

/* -----------------------
   Mapy obiektów
----------------------- */
$dzialki = [];
$nieruchomosci = [];
$dokumenty = [];

/* -----------------------
   DZIAŁKI
----------------------- */
foreach ($xml->xpath('//rcw:RCN_Dzialka') as $d) {

    $gid = (string)$d->attributes('gml', true)->id;
    $idDzialki = (string)$d->xpath('rcw:idDzialki')[0];

    if ($filter && !str_starts_with($idDzialki, $filter)) continue;

    $ring = [];
    foreach ($d->xpath('.//gml:pos') as $pos) {
        $parts = preg_split('/\s+/', trim((string)$pos));
        $ring[] = epsg2178_to_wgs84((float)$parts[0], (float)$parts[1]);
    }

    $dzialki[$gid] = [
        "type" => "Feature",
        "geometry" => [
            "type" => "Polygon",
            "coordinates" => [$ring]
        ],
        "properties" => [
            "idDzialki" => $idDzialki,
            "transakcje" => []
        ]
    ];
}

/* -----------------------
   NIERUCHOMOŚCI
----------------------- */
foreach ($xml->xpath('//rcw:RCN_Nieruchomosc') as $n) {

    $nid = (string)$n->attributes('gml', true)->id;

    $dzRefs = [];
    foreach ($n->xpath('rcw:dzialka') as $dz) {
        $dzRefs[] = (string)$dz->attributes('xlink', true)->href;
    }

    $data = json_decode(json_encode($n), true);
    unset($data['@attributes']); // usuń gml:id

    $nieruchomosci[$nid] = [
        "data" => $data,
        "dzialki" => $dzRefs
    ];
}

/* -----------------------
   DOKUMENTY
----------------------- */
foreach ($xml->xpath('//rcw:RCN_Dokument') as $d) {
    $did = (string)$d->attributes('gml', true)->id;
    $dokumenty[$did] = json_decode(json_encode($d), true);
}

/* -----------------------
   TRANSAKCJE
----------------------- */
foreach ($xml->xpath('//rcw:RCN_Transakcja') as $t) {

    $docRef = (string)$t->xpath('rcw:podstawaPrawna')[0]
        ->attributes('xlink', true)->href;

    $nierRefs = [];
    foreach ($t->xpath('rcw:nieruchomosc') as $nr) {
        $nierRefs[] = (string)$nr->attributes('xlink', true)->href;
    }

    $tData = json_decode(json_encode($t), true);
    unset($tData['@attributes']);

    foreach ($nierRefs as $nrid) {

        if (!isset($nieruchomosci[$nrid])) continue;

        foreach ($nieruchomosci[$nrid]['dzialki'] as $dzid) {

            if (!isset($dzialki[$dzid])) continue;

            $dzialki[$dzid]['properties']['transakcje'][] = [
                "transakcja" => $tData,
                "nieruchomosc" => $nieruchomosci[$nrid]['data'],
                "dokument" => $dokumenty[$docRef] ?? null
            ];
        }
    }
}

/* -----------------------
   OUTPUT GEOJSON
----------------------- */
echo json_encode([
    "type" => "FeatureCollection",
    "features" => array_values($dzialki)
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
