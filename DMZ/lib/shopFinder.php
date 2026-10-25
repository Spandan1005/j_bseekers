<?php
//Evans

const PLACES_URL = 'https://places.googleapis.com/v1/places:searchText';

const PLACES_FIELDS = 'places.id,places.displayName,places.formattedAddress,places.location,places.googleMapsUri';

const PLACES_TIMEOUT = 10;        // seconds. the frontend waits on this reply, so never hang
const PLACES_MAX_RESULTS = 10;
const PLACES_DEFAULT_RADIUS = 16093;
const PLACES_MAX_RADIUS = 50000;     

//since google places are broad, users now have fixed list of services.(note to Kurt)
const SHOP_SERVICES = [
'body_shop' => ['query' => 'auto body shop', 'type' => 'car_repair'],
'dealer'    => ['query' => 'car dealer',     'type' => 'car_dealer'],
'repair'    => ['query' => 'auto repair',    'type' => 'car_repair'],
'tire'      => ['query' => 'tire shop',      'type' => 'tire_shop'],
'car_wash'  => ['query' => 'car wash',       'type' => 'car_wash'],
];

function search_error($message) {
return ['status' => 'error', 'message' => $message];
}

function places_key() {
$env = @parse_ini_file(__DIR__ . '/../emails.env') ?: [];
return getenv('PLACES_API_KEY') ?: ($env['PLACES_API_KEY'] ?? '');
}

//
function places_miles($lat1, $lng1, $lat2, $lng2) {
$r = 3958.8; // earth radius in miles
$dLat = deg2rad($lat2 - $lat1);
$dLng = deg2rad($lng2 - $lng1);
$a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}


function places_build_body($service, $location, $make, $radius, $lat, $lng) {
$info = SHOP_SERVICES[$service];

$query = $info['query'];
if ($service === 'dealer' && $make !== '') {
$query = "$make dealer";
}
$query .= " near $location";

$body = [
'textQuery' => $query,
'includedType' => $info['type'],
'pageSize' => PLACES_MAX_RESULTS,
];


if ($lat !== null && $lng !== null) {
$body['locationBias'] = ['circle' => [
'center' => ['latitude' => $lat, 'longitude' => $lng],
'radius' => (float)$radius,
]];
}
return $body;
}


function places_post($body, $key) {
$url = getenv('PLACES_URL') ?: PLACES_URL;
$context = stream_context_create(['http' => [
'method' => 'POST',
'header' => "Content-Type: application/json\r\n"
          . "X-Goog-Api-Key: $key\r\n"
          . "X-Goog-FieldMask: " . PLACES_FIELDS . "\r\n",
'content' => json_encode($body),
'timeout' => PLACES_TIMEOUT,
'ignore_errors' => true,
]]);

$raw = @file_get_contents($url, false, $context);
if ($raw === false) {
return [0, null];
}

$code = 0;

if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s?/', $http_response_header[0], $m)) {
$code = (int)$m[1];
}
return [$code, json_decode($raw, true)];
}

function search_handle($request) {

$service = trim((string)($request['service'] ?? ''));
$location = trim((string)($request['location'] ?? ''));
$make = trim((string)($request['make'] ?? ''));


if (!isset(SHOP_SERVICES[$service])) {
return search_error('Unknown service type');
}
if (!preg_match('/^\d{5}$/', $location) && !preg_match("/^[A-Za-z0-9 ,.'-]{2,60}$/", $location)) {
return search_error('Location must be a 5 digit ZIP or a city and state');
}
if ($make !== '' && !preg_match('/^[A-Za-z0-9 -]{1,30}$/', $make)) {
return search_error('Invalid make');
}

$radius = (float)($request['radius_m'] ?? 0);
if ($radius <= 0 && isset($request['radius_miles'])) {
$radius = (float)$request['radius_miles'] * 1609.34;
}
if ($radius <= 0) {
$radius = PLACES_DEFAULT_RADIUS;
}
$radius = min($radius, PLACES_MAX_RADIUS);

$lat = null;
$lng = null;
if (isset($request['lat'], $request['lng']) && is_numeric($request['lat']) && is_numeric($request['lng'])) {
$lat = (float)$request['lat'];
$lng = (float)$request['lng'];
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
return search_error('Invalid coordinates');
}
}

$key = places_key();
if (!$key) {
return search_error('Places API key is missing on the DMZ server');
}

$body = places_build_body($service, $location, $make, $radius, $lat, $lng);
[$code, $data] = places_post($body, $key);

// logs for debugging
if ($code === 0) {
error_log('search_handle: could not reach google places');
return search_error('Could not reach the shop search service');
}
if ($code !== 200 || !is_array($data)) {
error_log("search_handle: google returned $code: " . ($data['error']['message'] ?? 'no message'));
if ($code === 400) {
return search_error('Shop search request was not accepted');
}
if ($code === 429) {
return search_error('Shop search is busy right now, try again later');
}
return search_error('Shop search failed');
}

// Forward to frontend
$shops = [];
foreach ($data['places'] ?? [] as $p) {
$shop = [
'place_id' => (string)($p['id'] ?? ''),
'name' => (string)($p['displayName']['text'] ?? ''),
'address' => (string)($p['formattedAddress'] ?? ''),
'lat' => isset($p['location']['latitude']) ? (float)$p['location']['latitude'] : null,
'lng' => isset($p['location']['longitude']) ? (float)$p['location']['longitude'] : null,
'maps_url' => (string)($p['googleMapsUri'] ?? ''),
'distance_miles' => null,
];
if ($shop['place_id'] === '' || $shop['name'] === '') {
continue;
}


if ($lat !== null && $shop['lat'] !== null) {
$miles = places_miles($lat, $lng, $shop['lat'], $shop['lng']);
if ($miles * 1609.34 > $radius) {
continue;
}
$shop['distance_miles'] = round($miles, 1);
}
$shops[] = $shop;
}

// closest first when we know distances
if ($lat !== null) {
usort($shops, fn($a, $b) => $a['distance_miles'] <=> $b['distance_miles']);
}

return ['status' => 'success', 'shops' => $shops];
}
